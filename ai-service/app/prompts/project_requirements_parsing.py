"""Prompt templates for parsing project requirement documents."""

from __future__ import annotations

PROJECT_PARSING_SYSTEM = """\
You are an expert technical project analyst specialising in extracting structured \
project information from unstructured requirement documents, RFPs, SOWs, and \
project briefs. You accurately identify:

1. **Project Name** -- the exact project or product name.
2. **Description** -- the main objectives, scope, and deliverables.
3. **Required Skills** -- specific technical skills and competencies needed.
4. **Required Technologies** -- specific tools, frameworks, platforms, and technologies.
5. **Complexity** -- project complexity assessment.
6. **Domain Context** -- the industry or business domain.
7. **Timeline** -- start and end dates if mentioned.

Always respond with valid JSON and nothing else. Do not include any text \
outside the JSON object.\
"""


def get_project_parsing_prompt(document_text: str) -> str:
    """Build the user-facing prompt for project requirement parsing."""

    return f"""\
Parse the following project requirement document and extract structured fields \
for a project creation form.

=== PROJECT REQUIREMENT DOCUMENT ===
{document_text}

=== INSTRUCTIONS ===
Extract and return a single JSON object with exactly these fields:

{{
  "name": "<project name or product name>",
  "description": "<full project description, objectives, scope, and deliverables>",
  "required_skills": ["<skill1>", "<skill2>", ...],
  "required_technologies": ["<tech1>", "<tech2>", ...],
  "complexity_level": "<low | medium | high | critical>",
  "domain_context": "<industry/business domain description>",
  "start_date": "<YYYY-MM-DD or null if not mentioned>",
  "end_date": "<YYYY-MM-DD or null if not mentioned>"
}}

Guidelines:
- For **name**: Extract the project/product name. If not explicit, derive a concise name from the document.
- For **description**: Combine objectives, scope, deliverables, and business context into a comprehensive description.
- For **required_skills**: Extract specific technical skills needed (e.g., "React", "Python", "System Design", "API Development").
- For **required_technologies**: Extract specific tools, frameworks, platforms (e.g., "AWS", "Docker", "PostgreSQL", "Kubernetes").
- For **complexity_level**: Assess based on scope, integrations, and technical depth:
  - "low" = simple CRUD, single integration, < 1 month
  - "medium" = moderate features, 2-3 integrations, 1-3 months
  - "high" = complex system, multiple integrations, 3-6 months
  - "critical" = enterprise-scale, mission-critical, 6+ months
- For **domain_context**: Identify the business domain (e.g., "E-commerce", "Healthcare", "Fintech", "EdTech").
- For **dates**: Extract in YYYY-MM-DD format. Use null if not mentioned.

If a field cannot be determined from the document, use sensible defaults \
(empty string for text, empty array for arrays, "medium" for complexity, null for dates).

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
