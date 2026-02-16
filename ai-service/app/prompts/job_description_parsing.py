"""Prompt templates for parsing job description documents."""

from __future__ import annotations

JOB_PARSING_SYSTEM = """\
You are an expert HR document parser specialising in extracting structured \
job posting information from unstructured job description documents. You \
accurately identify:

1. **Job Title** -- the exact role title.
2. **Description** -- the main responsibilities and role overview.
3. **Requirements** -- specific qualifications and requirements.
4. **Skills** -- both required and nice-to-have skills.
5. **Experience** -- years of experience expected.
6. **Employment Details** -- type of employment, location, salary.

Always respond with valid JSON and nothing else. Do not include any text \
outside the JSON object.\
"""


def get_job_parsing_prompt(document_text: str) -> str:
    """Build the user-facing prompt for job description parsing."""

    return f"""\
Parse the following job description document and extract structured fields \
for a job posting form.

=== JOB DESCRIPTION DOCUMENT ===
{document_text}

=== INSTRUCTIONS ===
Extract and return a single JSON object with exactly these fields:

{{
  "title": "<exact job title, e.g. Senior Backend Developer>",
  "description": "<full job description / responsibilities section>",
  "requirements": "<qualifications and requirements section>",
  "required_skills": ["<skill1>", "<skill2>", ...],
  "nice_to_have_skills": ["<skill1>", "<skill2>", ...],
  "min_experience": <integer, minimum years of experience, default 0>,
  "max_experience": <integer, maximum years of experience, default 10>,
  "employment_type": "<full_time | part_time | contract | intern>",
  "location": "<office location or Remote>",
  "salary_min": <number or null if not mentioned>,
  "salary_max": <number or null if not mentioned>
}}

Guidelines:
- For **title**: Extract the exact job title. If multiple titles exist, use the primary one.
- For **description**: Combine role overview, responsibilities, and what-you-will-do sections.
- For **requirements**: Combine qualifications, education, and certification requirements.
- For **required_skills**: Extract specific technical skills, tools, and technologies that are mandatory.
- For **nice_to_have_skills**: Extract skills mentioned as preferred, bonus, or nice-to-have.
- For **experience**: Extract min/max years. If only one number mentioned (e.g. "5+ years"), \
  set min to that number and max to min+5.
- For **employment_type**: Map to one of the exact values. Default to "full_time" if not specified.
- For **location**: Extract the city/region or "Remote" if remote-first.
- For **salary**: Extract numeric values only, without currency symbols. Use null if not mentioned.

If a field cannot be determined from the document, use sensible defaults \
(empty string for text, empty array for skills, 0/10 for experience, null for salary).

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
