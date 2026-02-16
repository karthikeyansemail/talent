"""Prompt templates for resume signal extraction (scoring-separated)."""

from __future__ import annotations

from app.models.requests import ResumeAnalysisRequest

RESUME_SIGNAL_EXTRACTION_SYSTEM = """\
You are an expert talent-acquisition AI specialising in resume screening and \
candidate evaluation. Your task is to extract objective, measurable signals \
from a resume — NOT to compute a final score.

You assess resumes for:

1. **Skill Depth** — not just keyword presence but evidence of real proficiency \
   (projects, metrics, duration of use).
2. **Experience Authenticity** — consistency of dates, plausible career \
   progression, specificity of accomplishments versus vague buzzwords.
3. **Job-Description Relevance** — how closely the candidate's background \
   aligns with the role's actual responsibilities and requirements.
4. **Resume Quality** — detecting keyword stuffing, generic language, \
   unverifiable claims, and distinguishing genuine expertise from surface-level \
   optimization.

Always respond with valid JSON and nothing else. Do not include any text outside \
the JSON object.\
"""


def get_resume_signal_extraction_prompt(request: ResumeAnalysisRequest) -> str:
    """Build the user-facing prompt for signal extraction (no overall score)."""

    required_skills_block = (
        "\n".join(f"  - {s}" for s in request.required_skills)
        if request.required_skills
        else "  (none specified)"
    )

    return f"""\
Extract objective signals from the following resume against the target role. \
Do NOT compute a final overall score — only extract individual signal values.

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
Extract signals and return a single JSON object with exactly these fields:

{{
  "skill_match_score": <float 0-100>,
  "experience_score": <float 0-100>,
  "relevance_score": <float 0-100>,
  "authenticity_score": <float 0-100>,
  "keyword_density": <float 0-100>,
  "generic_language": <float 0-100>,
  "verifiable_evidence": <float 0-100>,
  "career_progression": <float 0-100>,
  "quantified_claims": <float 0-100>,
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
  "explanation": "<detailed paragraph explaining the overall assessment>"
}}

=== SIGNAL DEFINITIONS ===

**Core Signals** (how well the candidate fits the role):
- **skill_match_score**: Proportion and depth of required skills demonstrated. \
  High = most required skills evidenced with real proficiency.
- **experience_score**: Relevance and duration of professional experience. \
  High = strong relevant experience at appropriate seniority.
- **relevance_score**: How well overall background maps to the job description. \
  High = background directly aligns with role responsibilities.
- **authenticity_score**: Consistency, specificity, and credibility of claims. \
  High = specific, verifiable, consistent career narrative.

**Quality Signals** (resume quality and authenticity indicators):
- **keyword_density**: 100 = natural language with skills woven into context; \
  0 = heavy keyword stuffing (skills listed without context, unnatural repetition, \
  SEO-style optimization). Look for: skills repeated 3+ times, skills listed \
  without any project/usage context, unusually long skill lists.
- **generic_language**: 100 = specific details, concrete examples, named tools; \
  0 = all buzzwords and generic phrases ("team player", "results-driven", \
  "leveraged synergies"). Look for: vague action verbs without measurable outcomes, \
  cliché phrases, absence of specific technologies or projects.
- **verifiable_evidence**: 100 = named employers, specific dates, concrete metrics, \
  verifiable project names; 0 = anonymous experience, missing dates, no company names, \
  unverifiable claims. Look for: company names, date ranges, project/product names, \
  technologies with versions.
- **career_progression**: 100 = logical career trajectory with increasing \
  responsibility, clear growth path; 0 = random jumps, unexplained gaps, lateral \
  or backward moves without context. Look for: title progression, increasing scope, \
  consistent industry/domain focus.
- **quantified_claims**: 100 = measurable achievements throughout (percentages, \
  dollar amounts, team sizes, timelines); 0 = no quantified results anywhere. \
  Look for: "increased X by Y%", "managed team of N", "reduced cost by $X", \
  specific numbers attached to accomplishments.

Include one entry in `skill_analysis` for every required skill listed above, \
plus any additional notable skills found in the resume.

If information is insufficient to score a dimension, assign a conservative \
score and note the gap in `concerns`.

Return ONLY the JSON object, no markdown fences, no commentary.\
"""
