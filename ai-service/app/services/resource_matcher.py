"""Service layer for matching employees to project resource requirements."""

from __future__ import annotations

import logging

from app.models.requests import ResourceMatchRequest
from app.models.responses import ResourceMatchResponse
from app.services.llm_client import llm_client
from app.prompts.resource_matching import (
    get_resource_matching_prompt,
    RESOURCE_MATCHING_SYSTEM,
)

logger = logging.getLogger(__name__)


async def match_project_resources(
    request: ResourceMatchRequest,
) -> ResourceMatchResponse:
    """Rank employees by suitability for a project using the configured LLM.

    Parameters
    ----------
    request:
        Contains the project requirements and candidate employee profiles.

    Returns
    -------
    ResourceMatchResponse
        Ranked list of employee matches with scores and explanations.

    Raises
    ------
    ValueError
        If the LLM returns a response that cannot be mapped to the response model.
    """
    logger.info(
        "Starting resource matching  project=%s  candidates=%d",
        request.project.name,
        len(request.employees),
    )

    prompt = get_resource_matching_prompt(request)
    result = await llm_client.generate_json(prompt, RESOURCE_MATCHING_SYSTEM)

    if not result:
        raise ValueError(
            "LLM returned an empty or unparseable response for resource matching."
        )

    try:
        response = ResourceMatchResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to ResourceMatchResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info(
        "Resource matching complete  matches=%d  top_score=%.1f",
        len(response.matches),
        response.matches[0].match_score if response.matches else 0.0,
    )
    return response
