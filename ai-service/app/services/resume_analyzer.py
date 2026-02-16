"""Service layer for AI-powered resume analysis."""

from __future__ import annotations

import logging

from app.models.requests import ResumeAnalysisRequest
from app.models.responses import ResumeAnalysisResponse, ResumeSignalResponse
from app.services.llm_client import llm_client
from app.prompts.resume_analysis import (
    get_resume_analysis_prompt,
    RESUME_ANALYSIS_SYSTEM,
)
from app.prompts.resume_signal_extraction import (
    get_resume_signal_extraction_prompt,
    RESUME_SIGNAL_EXTRACTION_SYSTEM,
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


async def extract_resume_signals(request: ResumeAnalysisRequest) -> ResumeSignalResponse:
    """Extract raw resume signals without computing an overall score.

    The overall score is computed by the PHP ScoringEngine using configurable
    per-organisation weights. This function only extracts the raw signal values.

    Parameters
    ----------
    request:
        Same payload as analyze_resume — resume text + job details.

    Returns
    -------
    ResumeSignalResponse
        9 numeric signals + qualitative analysis, no overall_score.
    """
    logger.info(
        "Starting resume signal extraction  job_title=%s  required_skills=%d",
        request.job_title,
        len(request.required_skills),
    )

    prompt = get_resume_signal_extraction_prompt(request)
    result = await llm_client.generate_json(prompt, RESUME_SIGNAL_EXTRACTION_SYSTEM)

    if not result:
        raise ValueError(
            "LLM returned an empty or unparseable response for signal extraction."
        )

    try:
        response = ResumeSignalResponse(**result)
    except Exception as exc:
        logger.error("Failed to map LLM output to ResumeSignalResponse: %s", exc)
        raise ValueError(
            f"LLM response did not conform to the expected schema: {exc}"
        ) from exc

    logger.info(
        "Signal extraction complete  skill=%.1f  experience=%.1f  recommendation=%s",
        response.skill_match_score,
        response.experience_score,
        response.recommendation,
    )
    return response
