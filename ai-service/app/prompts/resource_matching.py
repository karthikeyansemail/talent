"""Prompt templates for project-resource matching."""

from __future__ import annotations

import json as _json

from app.models.requests import ResourceMatchRequest

RESOURCE_MATCHING_SYSTEM = """\
You are an expert resource-allocation AI for a software organisation. Given a \
project's requirements and a set of employee skill profiles, you determine which \
employees are the best fit. You consider skill overlap, depth of expertise, \
domain experience, and potential for growth. You are fair, objective, and \
transparent in your reasoning.

Always respond with valid JSON and nothing else. Do not include any text outside \
the JSON object.\
"""


def get_resource_matching_prompt(request: ResourceMatchRequest) -> str:
    """Build the user-facing prompt for project-resource matching."""

    project = request.project

    required_skills_str = ", ".join(project.required_skills) if project.required_skills else "(none specified)"
    required_tech_str = ", ".join(project.required_technologies) if project.required_technologies else "(none specified)"

    employee_blocks: list[str] = []
    for emp in request.employees:
        block = (
            f"  ID: {emp.id}\n"
            f"  Name: {emp.name}\n"
            f"  Skills from Resume: {_json.dumps(emp.skills_from_resume, default=str)}\n"
            f"  Skills from Jira: {_json.dumps(emp.skills_from_jira, default=str)}\n"
            f"  Combined Profile: {_json.dumps(emp.combined_skill_profile, default=str)}"
        )
        employee_blocks.append(block)

    employees_section = "\n\n".join(
        f"  [{i + 1}]\n{block}" for i, block in enumerate(employee_blocks)
    )

    return f"""\
Match the employees below to the project requirements and rank them by fit.

=== PROJECT ===
Name: {project.name}
Description: {project.description}
Required Skills: {required_skills_str}
Required Technologies: {required_tech_str}
Complexity Level: {project.complexity_level}
Domain Context: {project.domain_context}

=== EMPLOYEES ({len(request.employees)} candidates) ===
{employees_section}

=== INSTRUCTIONS ===
Return a single JSON object with exactly this structure:

{{
  "matches": [
    {{
      "employee_id": <int>,
      "match_score": <float 0-100>,
      "strength_areas": ["<area 1>", "<area 2>", ...],
      "skill_gaps": ["<missing skill 1>", ...],
      "explanation": "<2-3 sentence justification>"
    }}
  ]
}}

Scoring guidance:
- **match_score**: Overall suitability for this project (0-100).
  - 80-100: Excellent fit -- covers most required skills at depth.
  - 60-79: Good fit -- solid overlap with minor gaps.
  - 40-59: Partial fit -- some relevant skills but notable gaps.
  - 0-39: Weak fit -- limited overlap.
- **strength_areas**: Specific skills or experiences that make the employee \
  valuable for this project.
- **skill_gaps**: Required skills or technologies the employee lacks or has \
  only surface-level knowledge of.
- **explanation**: Concise reasoning linking the employee's profile to the \
  project needs.

Important rules:
1. Include ALL employees in the `matches` array, even weak fits.
2. Sort `matches` by `match_score` descending (best fit first).
3. Consider both resume-derived and Jira-derived skills.
4. Where the combined profile is available, weight it most heavily.
5. Factor in domain context -- prior experience in the same domain is a plus.
6. For high-complexity projects, favour employees with deep/expert-level skills.

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
