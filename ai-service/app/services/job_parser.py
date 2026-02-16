"""Service layer for AI-powered job description parsing."""

from __future__ import annotations

import logging

from app.models.requests import JobParsingRequest
from app.models.responses import JobParsingResponse
from app.services.llm_client import llm_client
from app.prompts.job_description_parsing import (
    get_job_parsing_prompt,
    JOB_PARSING_SYSTEM,
)

logger = logging.getLogger(__name__)


async def parse_job_description(request: JobParsingRequest) -> JobParsingResponse:
    """Parse a job description document and extract structured fields.

    Parameters
    ----------
    request:
        Contains the full text extracted from a job description document.

    Returns
    -------
    JobParsingResponse
        Structured job posting fields extracted from the document.

    Raises
    ------
    ValueError
        If the LLM returns a response that cannot be mapped to the response model.
    """
    logger.info("Starting job description parsing  text_length=%d", len(request.document_text))

    prompt = get_job_parsing_prompt(request.document_text)
    result = await llm_client.generate_json(prompt, JOB_PARSING_SYSTEM)

    if not result:
        raise ValueError("LLM returned an empty or unparseable response for job parsing.")

    try:
        response = JobParsingResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to JobParsingResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info("Job description parsed successfully  title=%s", response.title)
    return response
