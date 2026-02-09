"""Prompt templates for resume analysis."""

from __future__ import annotations

from app.models.requests import ResumeAnalysisRequest

RESUME_ANALYSIS_SYSTEM = """\
You are an expert talent-acquisition AI specialising in resume screening and \
candidate evaluation. You assess resumes objectively and thoroughly, looking for:

1. **Skill Depth** -- not just keyword presence but evidence of real proficiency \
   (projects, metrics, duration of use).
2. **Experience Authenticity** -- consistency of dates, plausible career \
   progression, specificity of accomplishments versus vague buzzwords.
3. **Job-Description Relevance** -- how closely the candidate's background \
   aligns with the role's actual responsibilities and requirements.

Always respond with valid JSON and nothing else. Do not include any text outside \
the JSON object.\
"""


def get_resume_analysis_prompt(request: ResumeAnalysisRequest) -> str:
    """Build the user-facing prompt for resume analysis."""

    required_skills_block = (
        "\n".join(f"  - {s}" for s in request.required_skills)
        if request.required_skills
        else "  (none specified)"
    )

    return f"""\
Analyse the following resume against the target role and produce a structured \
JSON evaluation.

=== TARGET ROLE ===
Job Title: {request.job_title}
Experience Range: {request.min_experience} - {request.max_experience} years
Required Skills:
{required_skills_block}

Job Description:
{request.job_description}

=== RESUME TEXT ===
{request.resume_text}

=== INSTRUCTIONS ===
Evaluate the resume and return a single JSON object with exactly these fields:

{{
  "overall_score": <float 0-100>,
  "skill_match_score": <float 0-100>,
  "experience_score": <float 0-100>,
  "relevance_score": <float 0-100>,
  "authenticity_score": <float 0-100>,
  "skill_analysis": [
    {{
      "skill": "<skill name>",
      "level": "<beginner | intermediate | advanced | expert>",
      "evidence": "<quote or summary from the resume>",
      "score": <float 0-100>
    }}
  ],
  "experience_summary": "<2-4 sentence narrative of candidate experience>",
  "strengths": ["<strength 1>", "<strength 2>", ...],
  "concerns": ["<concern 1>", ...],
  "recommendation": "<strong_match | good_match | partial_match | weak_match>",
  "explanation": "<detailed paragraph explaining the overall recommendation>"
}}

Scoring guidance:
- **skill_match_score**: Proportion and depth of required skills demonstrated.
- **experience_score**: Relevance and duration of professional experience.
- **relevance_score**: How well overall background maps to the job description.
- **authenticity_score**: Consistency, specificity, and credibility of claims.
- **overall_score**: Weighted combination (skill 35%, experience 25%, relevance 25%, authenticity 15%).

Include one entry in `skill_analysis` for every required skill listed above, \
plus any additional notable skills found in the resume.

If information is insufficient to score a dimension, assign a conservative \
score and note the gap in `concerns`.

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
