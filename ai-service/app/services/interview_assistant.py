"""Service layer for AI-powered interview assistance."""

from __future__ import annotations

import logging

from app.services.llm_client import llm_client
from app.prompts.interview_questions import (
    INTERVIEW_QUESTIONS_SYSTEM,
    get_interview_questions_prompt,
)
from app.prompts.interview_evaluation import (
    ANSWER_EVALUATION_SYSTEM,
    get_answer_evaluation_prompt,
)
from app.prompts.interview_summary import (
    INTERVIEW_SUMMARY_SYSTEM,
    get_interview_summary_prompt,
)

logger = logging.getLogger(__name__)


async def generate_interview_questions(data: dict) -> dict:
    """Generate AI-resistant interview questions based on job requirements and conversation context."""

    logger.info(
        "Generating interview questions for job=%s, interview_type=%s",
        data.get("job_title"),
        data.get("interview_type"),
    )

    prompt = get_interview_questions_prompt(data)
    result = await llm_client.generate_json(prompt, INTERVIEW_QUESTIONS_SYSTEM)

    questions = result.get("questions", [])
    logger.info("Generated %d interview questions", len(questions))

    return result


async def evaluate_interview_answer(data: dict) -> dict:
    """Evaluate a candidate's answer to an interview question."""

    logger.info("Evaluating answer for skill_area=%s", data.get("skill_area"))

    prompt = get_answer_evaluation_prompt(data)
    result = await llm_client.generate_json(prompt, ANSWER_EVALUATION_SYSTEM)

    logger.info("Answer evaluation score=%s", result.get("score"))

    return result


async def generate_interview_summary(data: dict) -> dict:
    """Generate a comprehensive interview summary with hiring recommendation."""

    logger.info(
        "Generating interview summary for candidate=%s, duration=%d min",
        data.get("candidate_name"),
        data.get("duration_minutes", 0),
    )

    prompt = get_interview_summary_prompt(data)
    result = await llm_client.generate_json(prompt, INTERVIEW_SUMMARY_SYSTEM)

    logger.info(
        "Interview summary generated: rating=%s",
        result.get("overall_rating"),
    )

    return result
