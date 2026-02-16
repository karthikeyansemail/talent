import json
import os
from openai import AsyncOpenAI
from ..prompts.signal_analysis import SYSTEM_PROMPT, USER_PROMPT_TEMPLATE
from ..models.requests import SignalAnalysisRequest
from ..models.responses import SignalAnalysisResponse


async def analyze_signals(request: SignalAnalysisRequest) -> SignalAnalysisResponse:
    client = AsyncOpenAI(api_key=os.getenv("OPENAI_API_KEY"))

    signals_json = json.dumps(request.signals, indent=2)

    user_prompt = USER_PROMPT_TEMPLATE.format(
        employee_name=request.employee_name,
        period=request.period,
        signals_json=signals_json,
    )

    response = await client.chat.completions.create(
        model=os.getenv("OPENAI_MODEL", "gpt-4o-mini"),
        messages=[
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": user_prompt},
        ],
        response_format={"type": "json_object"},
        temperature=0.3,
    )

    result = json.loads(response.choices[0].message.content)

    return SignalAnalysisResponse(
        consistency_index=result.get("consistency_index", 50),
        recovery_signal=result.get("recovery_signal", 50),
        workload_pressure=result.get("workload_pressure", 50),
        context_switching_index=result.get("context_switching_index", 50),
        collaboration_density=result.get("collaboration_density", 50),
        summary=result.get("summary", ""),
        signal_insights=result.get("signal_insights", []),
    )
