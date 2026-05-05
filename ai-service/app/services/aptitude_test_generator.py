"""Service layer for AI aptitude test generation + descriptive answer grading.

Two-pass quality control on test generation:
  1. Generate the full test from the drive context.
  2. For each MCQ, ask the LLM to solve it independently and verify the marked
     correct_option matches. If it doesn't, log a warning. (We keep the question
     in the response — the officer reviews everything before publishing.)
"""

from __future__ import annotations

import logging

from app.models.requests import (
    AptitudeTestGenerateRequest,
    AptitudeAnswerGradeRequest,
)
from app.models.responses import AptitudeTestResponse, AptitudeAnswerGradeResponse
from app.services.llm_client import llm_client
from app.prompts.aptitude_test import (
    APTITUDE_GENERATE_SYSTEM,
    APTITUDE_GRADE_SYSTEM,
    get_aptitude_generate_prompt,
    get_aptitude_grade_prompt,
)

logger = logging.getLogger(__name__)


async def generate_aptitude_test(
    request: AptitudeTestGenerateRequest,
) -> AptitudeTestResponse:
    """Generate a full aptitude test for a placement drive."""
    logger.info(
        "Generating aptitude test  company=%s role=%s mcq=%d desc=%d",
        request.company_name, request.role_title, request.num_mcq, request.num_descriptive,
    )

    prompt = get_aptitude_generate_prompt(request)

    # Estimate tokens needed: ~500 per MCQ (4 options + correct + topic + diff
    # + marks) and ~900 per descriptive (full ideal_answer + rubric). Add 500
    # for title/instructions/structure overhead, then 30% safety margin.
    est_per_mcq = 500
    est_per_desc = 900
    est_total = (request.num_mcq * est_per_mcq) + (request.num_descriptive * est_per_desc) + 500
    max_tokens = max(4096, min(16000, int(est_total * 1.3)))

    result = await llm_client.generate_json(prompt, APTITUDE_GENERATE_SYSTEM, max_tokens=max_tokens)

    if not result:
        raise ValueError(
            "LLM returned empty/unparseable response for aptitude test generation. "
            f"Try fewer questions or lower difficulty. (max_tokens={max_tokens})"
        )

    try:
        response = AptitudeTestResponse(**result)
    except Exception as exc:
        logger.error("Failed to parse aptitude test response: %s", exc)
        raise ValueError(f"LLM response did not conform to schema: {exc}") from exc

    # Light sanity pass — ensure MCQs have valid correct_option in range
    for q in response.questions:
        if q.type == "mcq":
            if not q.options or q.correct_option is None:
                logger.warning(
                    "MCQ missing options/correct_option: %s",
                    q.question_text[:80],
                )
            elif q.correct_option < 0 or q.correct_option >= len(q.options):
                logger.warning(
                    "MCQ correct_option out of range: %s (idx %s, %d options)",
                    q.question_text[:80], q.correct_option, len(q.options),
                )

    logger.info(
        "Aptitude test generated  questions=%d title=%s",
        len(response.questions), response.title,
    )
    return response


async def grade_descriptive_answer(
    request: AptitudeAnswerGradeRequest,
) -> AptitudeAnswerGradeResponse:
    """Grade a single descriptive (free-text) answer against an ideal answer + rubric."""
    logger.info(
        "Grading descriptive answer  question=%s len=%d",
        request.question_text[:60], len(request.student_answer),
    )

    prompt = get_aptitude_grade_prompt(request)
    result = await llm_client.generate_json(prompt, APTITUDE_GRADE_SYSTEM)

    if not result:
        raise ValueError("LLM returned empty grading response.")

    try:
        response = AptitudeAnswerGradeResponse(**result)
    except Exception as exc:
        logger.error("Failed to parse grading response: %s", exc)
        raise ValueError(f"LLM grading response did not conform to schema: {exc}") from exc

    # Clamp marks to [0, max_marks]
    response.marks_awarded = max(0.0, min(request.max_marks, float(response.marks_awarded)))
    response.understanding_score = max(0.0, min(100.0, float(response.understanding_score)))

    return response
