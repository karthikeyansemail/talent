"""Service layer for AI-powered project requirement parsing."""

from __future__ import annotations

import logging

from app.models.requests import ProjectParsingRequest
from app.models.responses import ProjectParsingResponse
from app.services.llm_client import llm_client
from app.prompts.project_requirements_parsing import (
    get_project_parsing_prompt,
    PROJECT_PARSING_SYSTEM,
)

logger = logging.getLogger(__name__)


async def parse_project_requirements(request: ProjectParsingRequest) -> ProjectParsingResponse:
    """Parse a project requirement document and extract structured fields.

    Parameters
    ----------
    request:
        Contains the full text extracted from a project requirement document.

    Returns
    -------
    ProjectParsingResponse
        Structured project fields extracted from the document.
    """
    logger.info("Starting project requirements parsing  text_length=%d", len(request.document_text))

    prompt = get_project_parsing_prompt(request.document_text)
    result = await llm_client.generate_json(prompt, PROJECT_PARSING_SYSTEM)

    if not result:
        raise ValueError("LLM returned an empty or unparseable response for project parsing.")

    try:
        response = ProjectParsingResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to ProjectParsingResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info("Project requirements parsed successfully  name=%s", response.name)
    return response
