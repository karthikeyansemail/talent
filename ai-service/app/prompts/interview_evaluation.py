"""Prompt templates for AI-powered interview answer evaluation."""

from __future__ import annotations

ANSWER_EVALUATION_SYSTEM = """\
You are an expert technical interview evaluator. Evaluate the candidate's \
answer to an interview question.

Assess:
1. **Depth of understanding**: Does the answer show genuine comprehension \
   or surface-level knowledge?
2. **Practical experience**: Does the candidate reference real situations \
   or give only theoretical answers?
3. **Communication clarity**: Is the explanation clear and well-structured?
4. **AI-resistance check**: Does the answer sound rehearsed/generic \
   (possibly AI-assisted) or authentic/personal?

Score from 0-100 where:
- 80-100: Expert depth, specific examples, clear trade-off analysis
- 60-79: Good working knowledge, some specifics, reasonable reasoning
- 40-59: Surface level, mostly theoretical, lacks concrete examples
- 0-39: Incorrect, confused, or completely generic

Always respond with valid JSON and nothing else.\
"""


def get_answer_evaluation_prompt(data: dict) -> str:
    """Build the user-facing prompt for answer evaluation."""

    skills_block = (
        ", ".join(data.get("required_skills", []))
        or "not specified"
    )

    return f"""\
Question: {data.get('question', '')}
Skill Area: {data.get('skill_area', 'general')}
Job Title: {data.get('job_title', 'Unknown')}
Required Skills: {skills_block}

Candidate's Answer:
{data.get('answer', '')}

Evaluate this answer and respond with a JSON object:
{{
  "score": <0-100>,
  "depth": "surface|working|deep|expert",
  "strengths": ["strength1", "strength2"],
  "gaps": ["gap1", "gap2"],
  "follow_up_suggestion": "A follow-up question to probe deeper, or null",
  "ai_resistance_check": "Assessment of whether the answer shows genuine understanding vs rehearsed"
}}\
"""
