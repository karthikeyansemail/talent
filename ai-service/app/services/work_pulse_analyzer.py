"""Work Pulse analyzer service — derives qualitative work dimensions from task history."""

from __future__ import annotations

import logging

from app.models.requests import WorkPulseAnalyzeRequest
from app.models.responses import WorkPulseInsightResponse
from app.prompts.work_pulse_analysis import WORK_PULSE_SYSTEM, get_work_pulse_prompt
from app.services.llm_client import llm_client

logger = logging.getLogger(__name__)


async def analyze_work_pulse(request: WorkPulseAnalyzeRequest) -> WorkPulseInsightResponse:
    """Analyse employee task history and derive 5 qualitative work dimensions.

    Returns qualitative direction labels (Strong / Solid / Developing / Inconsistent)
    and a management-ready narrative — no numerical scores.
    """
    prompt = get_work_pulse_prompt(request)
    result = await llm_client.generate_json(prompt, WORK_PULSE_SYSTEM)

    if not result:
        raise ValueError("LLM returned empty or unparseable response for work pulse analysis")

    try:
        response = WorkPulseInsightResponse(**result)
    except Exception as exc:
        raise ValueError(f"LLM response did not conform to WorkPulseInsightResponse schema: {exc}") from exc

    logger.info(
        "Work pulse analysis complete  employee=%s  dimensions=%d",
        request.employee_name,
        len(response.dimensions),
    )
    return response
