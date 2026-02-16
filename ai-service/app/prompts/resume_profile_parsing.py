"""Prompt templates for extracting candidate profile fields from a resume."""

from __future__ import annotations

RESUME_PROFILE_SYSTEM = """\
You are an expert resume parser specialising in extracting biographical and \
professional information from resumes. You accurately identify:

1. **Personal Information** -- name, email, phone number.
2. **Current Employment** -- current company and job title.
3. **Experience** -- total years of professional experience.
4. **Skills** -- key technical and professional skills.

Always respond with valid JSON and nothing else. Do not include any text \
outside the JSON object.\
"""


def get_resume_profile_prompt(resume_text: str) -> str:
    """Build the user-facing prompt for resume profile extraction."""

    return f"""\
Parse the following resume and extract candidate profile information.

=== RESUME TEXT ===
{resume_text}

=== INSTRUCTIONS ===
Extract and return a single JSON object with exactly these fields:

{{
  "first_name": "<candidate's first name>",
  "last_name": "<candidate's last name>",
  "email": "<email address>",
  "phone": "<phone number with country code if available>",
  "current_company": "<current or most recent employer>",
  "current_title": "<current or most recent job title>",
  "experience_years": <integer, total years of professional experience>,
  "skills": ["<skill1>", "<skill2>", ...],
  "summary": "<1-2 sentence professional summary>"
}}

Guidelines:
- For **name**: Split into first and last name. If only one name is found, put it in first_name.
- For **email**: Extract the primary email address.
- For **phone**: Include country code if present.
- For **current_company**: Use the most recent employer listed.
- For **current_title**: Use the most recent job title listed.
- For **experience_years**: Calculate from the earliest work start date to present. \
  Round to the nearest integer.
- For **skills**: Extract the top 10-15 most prominent technical and professional skills.
- For **summary**: Write a brief professional summary based on the resume content.

If a field cannot be determined, use an empty string (or null for experience_years, \
empty array for skills).

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
