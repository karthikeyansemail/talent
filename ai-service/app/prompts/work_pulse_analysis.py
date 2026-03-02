"""Prompt definitions for AI Work Pulse analysis."""

from __future__ import annotations

from app.models.requests import WorkPulseAnalyzeRequest

WORK_PULSE_SYSTEM = """You are an expert engineering intelligence AI that reads employee work data (tasks, communication signals, sprint records) and surfaces qualitative patterns about how that person works.

Your job is NOT to judge character or assign scores. Instead, you produce factual, observable patterns that a team lead can discuss in a performance conversation. Base every statement on concrete evidence from the data provided.

Rules:
- Never use numerical scores (no "78/100", no percentages as scores)
- Never use character adjectives (no "hardworking", "lazy", "reliable", "unreliable")
- Cite only observable patterns: task counts, types, cycle times, story points, priorities, communication metrics, sprint accuracy, message tone signals
- When comm_signals include sentiment data (message_sentiment_score), interpret negative sentiment (< -20) as possible friction or stress signals worth exploring — do NOT say the person is disengaged or about to leave
- When comm_signals or sprint_records are present, incorporate them as evidence in the relevant dimensions
- direction must be exactly one of: "Strong", "Solid", "Developing", "Inconsistent"
- description must be 1-2 factual sentences with evidence from the data
- management_narrative must be 2-3 sentences suitable for a 1-on-1 conversation, no jargon, no character adjectives; if comm signals show declining patterns, note this as something to explore
- Always respond with valid JSON and nothing else — no markdown, no commentary

Dimension definitions:
- Complexity Handling: what complexity of work does this person take on? (story points, task types, priorities)
- Delivery Reliability: how consistently are tasks completed? (completion rate, spillover, done vs total; sprint planning accuracy when available)
- Execution Speed: how quickly does work flow through? (cycle time from creation to completion, aging tasks)
- Quality Orientation: how does this person handle quality work? (bug rates, bug resolution, high-priority completion)
- Scope & Impact: how broad and cross-functional is the work? (domains, task variety, tech variety, collaboration breadth from comm signals when available)
- Communication Quality: ONLY include this dimension when comm_signals are present. Assess communication engagement from observable patterns: message frequency, active days, collaborator breadth, channel diversity, avg message length, and message tone score. Use language like "tone patterns suggest..." or "communication signals show..." — never make predictions about intent or future behaviour."""


def get_work_pulse_prompt(request: WorkPulseAnalyzeRequest) -> str:
    """Build the user prompt for work pulse analysis."""

    task_lines = []
    for i, t in enumerate(request.tasks, 1):
        sp_str = f"{t.story_points} SP" if t.story_points else "no SP"
        labels_str = ", ".join(t.labels) if t.labels else "no labels"
        completed_str = f"completed {t.completed_at}" if t.completed_at else "not completed"
        task_lines.append(
            f"[{i}] {t.summary}\n"
            f"    Type: {t.type} | Status: {t.status} | Priority: {t.priority} | {sp_str}\n"
            f"    Created: {t.created_at} | {completed_str}\n"
            f"    Labels: {labels_str}"
        )

    tasks_block = "\n".join(task_lines)
    total = len(request.tasks)

    # Optional: communication & collaboration signals
    comm_block = ""
    if request.comm_signals:
        comm_lines = [
            f"  - [{s.source}] {s.metric_key}: {s.metric_value} {s.metric_unit} ({s.period})"
            for s in request.comm_signals
        ]
        comm_block = "\n=== COMMUNICATION & COLLABORATION SIGNALS ===\n" + "\n".join(comm_lines)

    # Optional: sprint planning accuracy
    sprint_block = ""
    if request.sprint_records:
        sprint_lines = []
        for s in request.sprint_records:
            if s.planned_points and s.planned_points > 0:
                completed = s.completed_points or 0
                acc = round(completed / s.planned_points * 100)
                sprint_lines.append(
                    f"  - {s.sprint_name}: planned {s.planned_points}pts/{s.tasks_planned}tasks "
                    f"→ completed {completed}pts/{s.tasks_completed}tasks ({acc}% accuracy)"
                )
            else:
                sprint_lines.append(
                    f"  - {s.sprint_name}: planned {s.tasks_planned} tasks "
                    f"→ completed {s.tasks_completed} tasks"
                )
        sprint_block = "\n=== SPRINT PLANNING ACCURACY ===\n" + "\n".join(sprint_lines)

    comm_dimension_instruction = ""
    if request.comm_signals:
        comm_dimension_instruction = """
    {
      "name": "Communication Quality",
      "direction": "<Strong|Solid|Developing|Inconsistent>",
      "description": "<1-2 factual sentences citing comm signal evidence — message frequency, active days, collaborators, tone score>"
    },"""

    return f"""Analyse the work data below for **{request.employee_name}** ({request.designation or 'N/A'}, {request.department or 'N/A'}).

=== TASK HISTORY ({total} tasks) ===
{tasks_block}{comm_block}{sprint_block}

=== INSTRUCTIONS ===
Return a single JSON object with exactly these fields:

{{
  "dimensions": [
    {{
      "name": "Complexity Handling",
      "direction": "<Strong|Solid|Developing|Inconsistent>",
      "description": "<1-2 factual sentences citing evidence from tasks>"
    }},
    {{
      "name": "Delivery Reliability",
      "direction": "<Strong|Solid|Developing|Inconsistent>",
      "description": "<1-2 factual sentences citing evidence from tasks>"
    }},
    {{
      "name": "Execution Speed",
      "direction": "<Strong|Solid|Developing|Inconsistent>",
      "description": "<1-2 factual sentences citing evidence from tasks>"
    }},
    {{
      "name": "Quality Orientation",
      "direction": "<Strong|Solid|Developing|Inconsistent>",
      "description": "<1-2 factual sentences citing evidence from tasks>"
    }},
    {{
      "name": "Scope & Impact",
      "direction": "<Strong|Solid|Developing|Inconsistent>",
      "description": "<1-2 factual sentences citing evidence from tasks>"
    }}{comm_dimension_instruction}
  ],
  "management_narrative": "<2-3 sentences a manager can read in a 1-on-1; if comm signals show notable patterns (declining activity, tone signals), include a brief mention as something to explore — factual, no jargon, no character adjectives>",
  "task_summary": "<brief statement of what was analyzed, e.g. 'Analyzed 34 tasks spanning Dec 2025 – Feb 2026'>"
}}

Return ONLY the JSON object. Do not include markdown fences, commentary, or any text outside the JSON."""
