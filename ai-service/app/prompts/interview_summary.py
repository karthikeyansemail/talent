"""Prompt templates for AI-powered interview summary generation."""

from __future__ import annotations

INTERVIEW_SUMMARY_SYSTEM = """\
You are an expert hiring evaluator. Summarize an interview session and \
provide a structured hiring recommendation.

Your summary should be objective, evidence-based, and actionable. Reference \
specific moments from the transcript to support your assessment.

Always respond with valid JSON and nothing else.\
"""


def get_interview_summary_prompt(data: dict) -> str:
    """Build the user-facing prompt for interview summary generation."""

    # Format transcript
    transcript_lines = []
    for turn in data.get("transcript", []):
        speaker = turn.get("speaker", "unknown").upper()
        text = turn.get("text", "")
        transcript_lines.append(f"[{speaker}]: {text}")
    transcript_block = "\n".join(transcript_lines) if transcript_lines else "(no transcript)"

    # Format Q&A evaluations
    qa_lines = []
    for qa in data.get("questions_and_evaluations", []):
        q = qa.get("question", "")
        a = qa.get("answer", "No answer recorded")
        ev = qa.get("evaluation", {})
        score = ev.get("score", "N/A") if isinstance(ev, dict) else "N/A"
        qa_lines.append(f"Q: {q}\nA: {a}\nScore: {score}")
    qa_block = "\n\n".join(qa_lines) if qa_lines else "(no Q&A data)"

    return f"""\
Job Title: {data.get('job_title', 'Unknown')}
Candidate: {data.get('candidate_name', 'Unknown')}
Interview Type: {data.get('interview_type', 'technical_round_1')}
Duration: {data.get('duration_minutes', 0)} minutes

=== TRANSCRIPT ===
{transcript_block}

=== QUESTIONS & EVALUATIONS ===
{qa_block}

Generate a comprehensive interview summary as a JSON object:
{{
  "overall_rating": "strong_yes|yes|neutral|no|strong_no",
  "technical_depth": <0-100>,
  "communication_score": <0-100>,
  "problem_solving_score": <0-100>,
  "strengths": ["strength1", "strength2", ...],
  "concerns": ["concern1", "concern2", ...],
  "key_moments": ["Notable moment 1", "Notable moment 2", ...],
  "narrative": "A 2-3 paragraph hiring recommendation narrative referencing specific moments from the interview",
  "suggested_next_steps": "Recommendation for next steps in the hiring process"
}}\
"""
