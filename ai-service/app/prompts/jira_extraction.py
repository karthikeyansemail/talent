"""Prompt templates for Jira signal extraction."""

from __future__ import annotations

from app.models.requests import JiraSignalRequest

JIRA_EXTRACTION_SYSTEM = """\
You are an expert engineering-intelligence AI that infers developer skills, \
technical depth, and work patterns from Jira task histories. You reason carefully \
about what technologies, frameworks, domains, and soft skills are implied by each \
task's summary, description, labels, type, and complexity.

Always respond with valid JSON and nothing else. Do not include any text outside \
the JSON object.\
"""


def get_jira_extraction_prompt(request: JiraSignalRequest) -> str:
    """Build the user-facing prompt for Jira signal extraction."""

    task_lines: list[str] = []
    for t in request.tasks:
        parts = [
            f"Key: {t.key}",
            f"Summary: {t.summary}",
            f"Type: {t.type}",
            f"Status: {t.status}",
            f"Priority: {t.priority}",
        ]
        if t.description:
            # Truncate very long descriptions to keep the prompt manageable.
            desc = t.description[:600] + ("..." if len(t.description) > 600 else "")
            parts.append(f"Description: {desc}")
        if t.labels:
            parts.append(f"Labels: {', '.join(t.labels)}")
        if t.story_points is not None:
            parts.append(f"Story Points: {t.story_points}")
        if t.resolved_at:
            parts.append(f"Resolved: {t.resolved_at}")
        task_lines.append("\n    ".join(parts))

    tasks_block = "\n\n".join(
        f"  [{i + 1}]\n    {line}" for i, line in enumerate(task_lines)
    )

    return f"""\
Analyse the Jira task history below for employee **{request.employee_name}** and \
extract skill signals and work patterns.

=== JIRA TASKS ({len(request.tasks)} total) ===
{tasks_block}

=== INSTRUCTIONS ===
Return a single JSON object with exactly these fields:

{{
  "extracted_skills": [
    {{
      "skill": "<skill or technology name>",
      "confidence": <float 0.0-1.0>,
      "depth": "<surface | working | deep | expert>",
      "evidence_count": <int, number of tasks that evidence this skill>,
      "last_used": "<ISO date or empty string if unknown>"
    }}
  ],
  "work_patterns": {{
    "complexity_preference": "<low | medium | high | mixed>",
    "avg_story_points": <float>,
    "domains": ["<domain 1>", "<domain 2>", ...],
    "consistency_score": <float 0-100>
  }},
  "summary": "<3-5 sentence narrative describing the employee's capabilities, \
preferred work style, and growth areas>"
}}

Guidelines:
- Infer skills from task summaries, descriptions, labels, and types. Consider \
  programming languages, frameworks, databases, cloud services, DevOps tools, \
  soft skills (e.g. mentoring, code review), and domain expertise.
- **confidence** reflects how certain you are the person truly possesses the skill \
  (1.0 = unambiguous, 0.5 = plausible, < 0.3 = speculative).
- **depth**: surface = mentioned/touched, working = regular use, deep = significant \
  ownership, expert = leads or architects in this area.
- **complexity_preference**: derive from story points distribution and task types.
- **avg_story_points**: if story points are missing for some tasks, estimate based \
  on comparable tasks; set to 0 if no information at all.
- **consistency_score**: regularity and reliability of delivery (100 = very \
  consistent, 0 = erratic).
- List skills in descending order of confidence.

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
