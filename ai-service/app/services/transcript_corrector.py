"""Service for LLM-powered transcript correction and code extraction from screenshots."""

from __future__ import annotations

import logging

from app.services.llm_client import llm_client

logger = logging.getLogger(__name__)

CORRECTION_SYSTEM = """You are a transcript correction assistant for a technical interview platform.
Your job is to fix speech-to-text transcription errors using domain context.

Common ASR errors to fix:
- "coffee" → "copy" (in coding context)
- "reduct" → "Redux"
- "java strip" → "JavaScript"
- "no JS" → "Node.js"
- "sequel" → "SQL"
- "pie thon" → "Python"
- "doc or" → "Docker"
- "cube net ease" → "Kubernetes"
- "get hub" → "GitHub"
- "see eye" → "CI" (as in CI/CD)
- "API eye" → "API"

Rules:
1. Only fix clear transcription errors — don't rephrase or improve grammar
2. Keep the original meaning and sentence structure
3. Use the job context and vocabulary to resolve ambiguous words
4. If a word is correct, leave it unchanged
5. Return the corrected texts in the same order

Return JSON: {"corrections": ["corrected text 1", "corrected text 2", ...], "new_vocabulary": ["term1", "term2"]}
The new_vocabulary should contain any new technical terms you identified that should be added to the phrase hints."""


async def correct_transcript(data: dict) -> dict:
    """Correct transcription errors using job context."""

    texts = data.get("texts", [])
    vocabulary = data.get("vocabulary", [])
    job_title = data.get("job_title", "")
    required_skills = data.get("required_skills", [])
    job_description = data.get("job_description", "")

    if not texts:
        return {"corrections": [], "new_vocabulary": []}

    prompt = f"""Fix transcription errors in these interview transcript segments.

Job Context:
- Position: {job_title}
- Required Skills: {', '.join(required_skills)}
- Description: {job_description[:300]}

Known Vocabulary: {', '.join(vocabulary[:50])}

Transcript segments to correct:
"""
    for i, text in enumerate(texts):
        prompt += f"{i + 1}. {text}\n"

    prompt += "\nReturn corrected versions preserving original meaning. Only fix clear ASR errors."

    try:
        result = await llm_client.generate_json(prompt, CORRECTION_SYSTEM)
        corrections = result.get("corrections", texts)
        new_vocab = result.get("new_vocabulary", [])

        # Ensure we have the right number of corrections
        if len(corrections) != len(texts):
            corrections = texts

        logger.info("Corrected %d transcript segments, found %d new vocab terms", len(texts), len(new_vocab))
        return {"corrections": corrections, "new_vocabulary": new_vocab}

    except Exception as exc:
        logger.exception("Transcript correction failed")
        return {"corrections": texts, "new_vocabulary": []}


CODE_EXTRACTION_SYSTEM = """You are a code extraction assistant for technical interviews. Given a screenshot from a screen sharing session, extract ONLY actual programming source code.

CRITICAL — What IS code:
- Source code in an IDE, text editor, terminal, or code playground (VS Code, IntelliJ, Sublime, vim, CodePen, LeetCode editor, HackerRank, etc.)
- Code snippets in a browser console or developer tools
- Terminal/shell commands and output

CRITICAL — What is NOT code (return empty for these):
- Video conferencing UI (Google Meet, Zoom, Teams — participant names, chat messages, captions, subtitles)
- Meeting transcripts, captions, or closed captions overlaid on the video
- Browser pages showing documentation, articles, or web content (unless it contains a code block)
- Slide presentations, PDFs, or word documents (unless they contain code snippets)
- Desktop, taskbar, file explorers, or system UI
- Chat windows, email, or messaging apps

Rules:
1. Extract the code exactly as written — preserve indentation, variable names, and syntax
2. If the screenshot shows a video call, meeting UI, or any non-code content, ALWAYS return empty
3. If code is partially visible in an actual code editor, extract what you can see
4. Include the programming language if identifiable
5. Don't add code that isn't visible in the image
6. Spoken text, meeting captions, or chat messages are NEVER code — always return empty for these

Return JSON: {"code": "extracted code here", "language": "python/javascript/etc", "description": "brief description of what the code does"}
If no actual source code is visible, return: {"code": "", "language": "", "description": ""}"""


async def extract_code_from_screenshot(data: dict) -> dict:
    """Extract code from a screenshot using Vision AI."""

    image_base64 = data.get("image", "")
    if not image_base64:
        return {"code": "", "language": "", "description": ""}

    try:
        # Use the LLM with vision capability
        prompt = "Extract ONLY actual programming source code from this screenshot. If the image shows a video call, meeting UI, chat, captions, or any non-code content, return empty. Only return code if you see an actual code editor, IDE, terminal, or code playground."

        result = await llm_client.generate_json_with_image(
            prompt, CODE_EXTRACTION_SYSTEM, image_base64
        )

        code = result.get("code", "")
        language = result.get("language", "")
        description = result.get("description", "")

        if code:
            logger.info("Extracted %d chars of %s code: %s", len(code), language, description[:50])

        return {"code": code, "language": language, "description": description}

    except Exception as exc:
        logger.exception("Code extraction from screenshot failed")
        return {"code": "", "language": "", "description": ""}
