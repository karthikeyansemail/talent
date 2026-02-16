"""Service layer for AI-powered resume profile extraction."""

from __future__ import annotations

import logging

from app.models.requests import ResumeProfileRequest
from app.models.responses import ResumeProfileResponse
from app.services.llm_client import llm_client
from app.prompts.resume_profile_parsing import (
    get_resume_profile_prompt,
    RESUME_PROFILE_SYSTEM,
)

logger = logging.getLogger(__name__)


async def parse_resume_profile(request: ResumeProfileRequest) -> ResumeProfileResponse:
    """Extract candidate profile fields from a resume using the configured LLM.

    Parameters
    ----------
    request:
        Contains the full text extracted from a resume document.

    Returns
    -------
    ResumeProfileResponse
        Structured candidate profile fields extracted from the resume.

    Raises
    ------
    ValueError
        If the LLM returns a response that cannot be mapped to the response model.
    """
    logger.info("Starting resume profile parsing  text_length=%d", len(request.resume_text))

    prompt = get_resume_profile_prompt(request.resume_text)
    result = await llm_client.generate_json(prompt, RESUME_PROFILE_SYSTEM)

    if not result:
        raise ValueError("LLM returned an empty or unparseable response for resume parsing.")

    try:
        response = ResumeProfileResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to ResumeProfileResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info(
        "Resume profile parsed successfully  name=%s %s",
        response.first_name,
        response.last_name,
    )
    return response
