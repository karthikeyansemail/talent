"""Unified LLM client supporting OpenAI and Anthropic providers."""

from __future__ import annotations

import json
import logging
import re

from openai import AsyncOpenAI
from anthropic import AsyncAnthropic

from app.config import settings

logger = logging.getLogger(__name__)


class LLMClient:
    """Thin abstraction over OpenAI and Anthropic chat / messages APIs.

    The active provider is chosen at startup via the ``LLM_PROVIDER``
    environment variable (defaults to ``"openai"``).
    """

    def __init__(self) -> None:
        self.provider: str = settings.llm_provider.lower()

        if self.provider == "openai":
            self.client = AsyncOpenAI(api_key=settings.openai_api_key)
            self.model = settings.openai_model
        elif self.provider == "anthropic":
            self.client = AsyncAnthropic(api_key=settings.anthropic_api_key)
            self.model = settings.anthropic_model
        else:
            raise ValueError(
                f"Unsupported LLM provider: '{self.provider}'. "
                "Choose 'openai' or 'anthropic'."
            )

        logger.info(
            "LLMClient initialised  provider=%s  model=%s",
            self.provider,
            self.model,
        )

    # ------------------------------------------------------------------
    # Core generation
    # ------------------------------------------------------------------

    async def generate(self, prompt: str, system_message: str = "") -> str:
        """Send a prompt to the configured LLM and return the raw text response.

        Parameters
        ----------
        prompt:
            The user-facing prompt / question.
        system_message:
            An optional system-level instruction prepended to the conversation.

        Returns
        -------
        str
            The model's text reply.
        """
        try:
            if self.provider == "openai":
                return await self._generate_openai(prompt, system_message)
            else:
                return await self._generate_anthropic(prompt, system_message)
        except Exception:
            logger.exception("LLM generation failed (provider=%s)", self.provider)
            raise

    async def _generate_openai(self, prompt: str, system_message: str) -> str:
        messages: list[dict] = []
        if system_message:
            messages.append({"role": "system", "content": system_message})
        messages.append({"role": "user", "content": prompt})

        response = await self.client.chat.completions.create(
            model=self.model,
            messages=messages,
            temperature=0.2,
            max_tokens=4096,
        )
        return response.choices[0].message.content or ""

    async def _generate_anthropic(self, prompt: str, system_message: str) -> str:
        kwargs: dict = {
            "model": self.model,
            "max_tokens": 4096,
            "temperature": 0.2,
            "messages": [{"role": "user", "content": prompt}],
        }
        if system_message:
            kwargs["system"] = system_message

        response = await self.client.messages.create(**kwargs)

        # Anthropic responses contain a list of content blocks.
        parts: list[str] = []
        for block in response.content:
            if hasattr(block, "text"):
                parts.append(block.text)
        return "".join(parts)

    # ------------------------------------------------------------------
    # JSON generation helper
    # ------------------------------------------------------------------

    async def generate_json(self, prompt: str, system_message: str = "") -> dict:
        """Generate a response and parse it as JSON.

        The method is lenient: it will attempt to extract a JSON object from
        markdown fenced code blocks (````json ... ````), or fall back to parsing
        the raw response text directly.

        Returns
        -------
        dict
            Parsed JSON object, or an empty ``dict`` on failure.
        """
        raw = await self.generate(prompt, system_message)

        # Try to extract from fenced code blocks first.
        code_block_match = re.search(
            r"```(?:json)?\s*\n?(.*?)\n?\s*```", raw, re.DOTALL
        )
        json_text = code_block_match.group(1).strip() if code_block_match else raw.strip()

        try:
            parsed = json.loads(json_text)
            if isinstance(parsed, dict):
                return parsed
            logger.warning("LLM returned non-object JSON; wrapping is skipped.")
            return parsed  # type: ignore[return-value]
        except json.JSONDecodeError:
            # Last-resort: try to find the first { ... } or [ ... ] span
            brace_match = re.search(r"\{[\s\S]*\}", json_text)
            if brace_match:
                try:
                    return json.loads(brace_match.group(0))
                except json.JSONDecodeError:
                    pass
            logger.error("Failed to parse LLM response as JSON:\n%s", raw[:500])
            return {}


# Module-level singleton so services can simply import and use it.
llm_client = LLMClient()
