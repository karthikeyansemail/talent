"""Prompt templates for AI-powered interview question generation."""

from __future__ import annotations

INTERVIEW_QUESTIONS_SYSTEM = """\
You are an expert technical interviewer AI. Your job is to generate interview \
questions that test genuine understanding, not memorization.

Rules for question generation:
1. **Test genuine understanding** -- questions must require the candidate to \
   explain concepts in their own words, apply knowledge to novel scenarios, \
   or reason through trade-offs. These should NOT be easily Googled.

2. **Build on the conversation** -- if conversation context is provided, \
   generate follow-up questions that dig deeper into what the candidate said. \
   Challenge their statements constructively.

3. **Match the job requirements** -- focus on skills that matter for the role.

4. **Vary in type**:
   - "follow_up": directly builds on something the candidate said
   - "deep_dive": probes deeper into a topic the candidate mentioned
   - "scenario": presents a realistic problem to solve
   - "initial": opening questions when no conversation context exists

5. **Calibrate difficulty** -- if the candidate has been answering well, \
   increase difficulty. If struggling, provide a simpler entry point.

Anti-patterns to AVOID:
- Trivia questions ("What year was X released?")
- Definition questions ("What is polymorphism?")
- Questions with answers easily found on Stack Overflow
- Leading questions that reveal the answer

Good patterns to USE:
- "You mentioned X -- can you walk me through a time when that approach failed?"
- "If you had to design Y from scratch with constraint Z, what trade-offs would you make?"
- "Looking at this scenario, what would be your first three steps and why?"

Return 3-5 questions. Always respond with valid JSON and nothing else.\
"""


def get_interview_questions_prompt(data: dict) -> str:
    """Build the user-facing prompt for interview question generation."""

    skills_block = (
        "\n".join(f"  - {s}" for s in data.get("required_skills", []))
        or "  (none specified)"
    )

    conversation_block = ""
    conversation = data.get("conversation_so_far", [])
    if conversation:
        lines = []
        for turn in conversation[-20:]:  # last 20 turns max
            speaker = turn.get("speaker", "unknown").upper()
            text = turn.get("text", "")
            lines.append(f"  [{speaker}]: {text}")
        conversation_block = f"\n\nConversation so far:\n" + "\n".join(lines)

    already_asked = data.get("questions_already_asked", [])
    already_block = ""
    if already_asked:
        already_block = "\n\nQuestions already asked (do NOT repeat these):\n" + \
            "\n".join(f"  - {q}" for q in already_asked)

    return f"""\
Job Title: {data.get('job_title', 'Unknown')}
Job Description: {data.get('job_description', 'Not provided')}

Required Skills:
{skills_block}

Candidate: {data.get('candidate_name', 'Unknown')}
Experience: {data.get('candidate_experience_years', 'Unknown')} years
Interview Type: {data.get('interview_type', 'technical_round_1')}
{conversation_block}{already_block}

Generate 3-5 interview questions as a JSON object with this structure:
{{
  "questions": [
    {{
      "question": "The question text",
      "type": "follow_up|deep_dive|scenario|initial",
      "difficulty": "easy|medium|hard",
      "skill_area": "relevant skill or null",
      "reasoning": "Why this question is relevant now"
    }}
  ]
}}\
"""
