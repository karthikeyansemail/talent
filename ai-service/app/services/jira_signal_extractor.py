"""Service layer for extracting skill signals from Jira task history."""

from __future__ import annotations

import logging

from app.models.requests import JiraSignalRequest
from app.models.responses import JiraSignalResponse
from app.services.llm_client import llm_client
from app.prompts.jira_extraction import (
    get_jira_extraction_prompt,
    JIRA_EXTRACTION_SYSTEM,
)

logger = logging.getLogger(__name__)


async def extract_jira_signals(request: JiraSignalRequest) -> JiraSignalResponse:
    """Extract skill signals and work patterns from an employee's Jira tasks.

    Parameters
    ----------
    request:
        Contains the employee name and their completed Jira tasks.

    Returns
    -------
    JiraSignalResponse
        Extracted skills, work patterns, and a narrative summary.

    Raises
    ------
    ValueError
        If the LLM returns a response that cannot be mapped to the response model.
    """
    logger.info(
        "Starting Jira signal extraction  employee=%s  tasks=%d",
        request.employee_name,
        len(request.tasks),
    )

    prompt = get_jira_extraction_prompt(request)
    result = await llm_client.generate_json(prompt, JIRA_EXTRACTION_SYSTEM)

    if not result:
        raise ValueError(
            "LLM returned an empty or unparseable response for Jira signal extraction."
        )

    try:
        response = JiraSignalResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to JiraSignalResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info(
        "Jira signal extraction complete  skills_found=%d  summary_len=%d",
        len(response.extracted_skills),
        len(response.summary),
    )
    return response
