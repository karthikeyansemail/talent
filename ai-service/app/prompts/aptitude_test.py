"""Prompts for AI aptitude test generation + descriptive answer grading.

Education vertical — see app/services/aptitude_test_generator.py.
"""

APTITUDE_GENERATE_SYSTEM = """You are an expert placement-prep test designer for engineering colleges in India.
You generate aptitude tests that companies use to filter candidates at campus drives.

Strict rules:
- For MCQ questions: produce exactly 4 plausible options where exactly ONE is correct.
  The correct_option field must be the 0-based index of the correct option.
- For descriptive questions: produce a question that requires conceptual explanation
  in 80-150 words. Always include a brief ideal_answer and 3-5 rubric_points the
  student must address to score well. These are graded by AI later — not just
  keyword match — so framing should reward understanding.
- Mix difficulty across the test (easy/medium/hard) — do not make everything hard.
- Topics should match the requested topic_mix and tilt toward the role's required skills.
- Avoid trick questions, ambiguous wordings, or questions with multiple defensible answers.
- For technical questions, include a small code snippet in the `context` field rather
  than embedding code in question_text.
- Output VALID JSON only, no commentary.
"""


def get_aptitude_generate_prompt(req) -> str:
    """Build the user prompt from an AptitudeTestGenerateRequest."""
    skills = ", ".join(req.required_skills) if req.required_skills else "general fundamentals"
    courses = ", ".join(req.eligible_courses) if req.eligible_courses else "any engineering branch"
    topics = ", ".join(req.topic_mix)

    return f"""Generate an aptitude test for the following placement drive:

Company: {req.company_name}
Role: {req.role_title}
{f'Brief: {req.role_description}' if req.role_description else ''}
Eligible courses: {courses}
Skills the test should cover: {skills}
Topic mix: {topics}

Requirements:
- Produce {req.num_mcq} MCQ questions and {req.num_descriptive} descriptive questions.
- Overall difficulty: {req.difficulty} (vary across questions, but center around this level).
- For descriptive questions, prefer concept-explanation prompts (e.g. "Explain how X
  differs from Y and when you'd choose each") because student prose reveals real
  understanding better than MCQ guesses.
- Title and instructions should be concise and student-facing.

Return VALID JSON in this exact schema:

{{
    "title": "string — suggested test title (e.g. 'TechCorp SWE Aptitude Test')",
    "instructions": "string — 2-3 sentence instructions shown to students",
    "questions": [
        {{
            "type": "mcq" | "descriptive",
            "question_text": "string",
            "context": "string (optional — code snippet, scenario, or empty)",
            "topic": "Quantitative | Logical Reasoning | Verbal | Technical | OOP | DSA | DBMS | etc.",
            "difficulty": "easy | medium | hard",
            "marks": 1,
            // For mcq:
            "options": ["A", "B", "C", "D"],
            "correct_option": 0,
            // For descriptive:
            "ideal_answer": "string — gold-standard answer (60-150 words)",
            "rubric_points": ["point 1", "point 2", "point 3"],
            "expected_word_count": 100
        }}
    ]
}}

Each question must include all fields shown — use empty values for fields that
don't apply to its type (e.g. options=[] and correct_option=null for descriptive,
ideal_answer="" and rubric_points=[] for MCQ).
"""


# ─── Descriptive answer grading ─────────────────────────

APTITUDE_GRADE_SYSTEM = """You are a fair and rigorous evaluator of placement-prep aptitude answers.
You grade descriptive (paragraph) answers by comparing student responses to the
provided ideal answer and rubric points.

Strict rules:
- Grade conceptual understanding, NOT keyword match. A student who explains correctly
  in their own words scores high even if their wording differs from the ideal.
- Grade demonstrated reasoning, NOT length. A clear short answer beats verbose hand-waving.
- Be skeptical of generic / surface-level answers (likely copy-paste). If the answer reads
  like a chatbot or textbook quote without applying to the question, dock points.
- Be skeptical of answers that mention only buzzwords without applying them. Reward
  applied understanding.
- For each rubric point, decide if the student covered it (true/false).
- understanding_score is 0-100 measuring true conceptual grasp (independent of rubric coverage):
  · 0-20: nothing relevant or off-topic
  · 21-40: vague awareness, no real understanding
  · 41-60: partial understanding with significant gaps
  · 61-80: solid understanding with minor gaps
  · 81-100: confident, accurate, applied understanding
- ai_feedback is 2-3 sentences for the student — what they got right + one specific
  thing to improve. Be constructive, not generic.
- Output VALID JSON only, no commentary.
"""


def get_aptitude_grade_prompt(req) -> str:
    """Build user prompt for grading one descriptive answer."""
    rubric = "\n".join(f"  - {p}" for p in req.rubric_points) if req.rubric_points else "  (no rubric points)"
    context = f"Context shown with question:\n{req.context}\n\n" if req.context else ""

    return f"""Grade this descriptive answer.

Question:
{req.question_text}

{context}Ideal answer (for reference):
{req.ideal_answer}

Rubric points the student should cover:
{rubric}

Maximum marks: {req.max_marks}

Student's answer:
{req.student_answer}

Return VALID JSON in this exact schema:

{{
    "marks_awarded": 0.0,           // out of {req.max_marks}, can be fractional (e.g. 3.5)
    "understanding_score": 0,       // 0-100 conceptual grasp
    "rubric_coverage": [true, false, ...],  // one bool per rubric point in same order
    "ai_feedback": "string — 2-3 sentences for the student"
}}
"""
