"""Service layer for AI-powered resume analysis."""

from __future__ import annotations

import logging

from app.models.requests import ResumeAnalysisRequest
from app.models.responses import ResumeAnalysisResponse
from app.services.llm_client import llm_client
from app.prompts.resume_analysis import (
    get_resume_analysis_prompt,
    RESUME_ANALYSIS_SYSTEM,
)

logger = logging.getLogger(__name__)


async def analyze_resume(request: ResumeAnalysisRequest) -> ResumeAnalysisResponse:
    """Analyse a resume against a job description using the configured LLM.

    Parameters
    ----------
    request:
        Contains the resume text, target job details, and scoring parameters.

    Returns
    -------
    ResumeAnalysisResponse
        Structured analysis with scores, skill breakdown, and recommendation.

    Raises
    ------
    ValueError
        If the LLM returns a response that cannot be mapped to the response model.
    """
    logger.info(
        "Starting resume analysis  job_title=%s  required_skills=%d",
        request.job_title,
        len(request.required_skills),
    )

    prompt = get_resume_analysis_prompt(request)
    result = await llm_client.generate_json(prompt, RESUME_ANALYSIS_SYSTEM)

    if not result:
        raise ValueError(
            "LLM returned an empty or unparseable response for resume analysis."
        )

    try:
        response = ResumeAnalysisResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to ResumeAnalysisResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info(
        "Resume analysis complete  overall_score=%.1f  recommendation=%s",
        response.overall_score,
        response.recommendation,
    )
    return response
