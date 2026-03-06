"""Audio transcription service using faster-whisper (local Whisper model)."""

from __future__ import annotations

import io
import logging
import tempfile
from pathlib import Path

logger = logging.getLogger(__name__)

# Lazy-load the model on first use to avoid slow startup
_model = None
_MODEL_SIZE = "base"  # good balance of speed vs accuracy on CPU


def _get_model():
    global _model
    if _model is None:
        logger.info("Loading Whisper model '%s' (first load downloads ~150 MB)...", _MODEL_SIZE)
        from faster_whisper import WhisperModel
        _model = WhisperModel(_MODEL_SIZE, device="cpu", compute_type="int8")
        logger.info("Whisper model loaded successfully.")
    return _model


async def transcribe_audio(audio_bytes: bytes, language: str = "en") -> str:
    """Transcribe audio bytes using local Whisper model.

    Parameters
    ----------
    audio_bytes:
        Raw audio file content (webm, wav, mp3, etc.)
    language:
        Language code for transcription (default: English).

    Returns
    -------
    str
        Transcribed text, or empty string if nothing detected.
    """
    if not audio_bytes or len(audio_bytes) < 100:
        return ""

    # Write to a temp file because faster-whisper needs a file path
    suffix = ".webm"
    with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as tmp:
        tmp.write(audio_bytes)
        tmp_path = tmp.name

    try:
        model = _get_model()
        segments, info = model.transcribe(
            tmp_path,
            language=language,
            beam_size=3,
            vad_filter=False,  # disabled — system audio levels can be low
        )
        text_parts = [seg.text.strip() for seg in segments if seg.text.strip()]
        result = " ".join(text_parts)
        logger.info(
            "Transcribed %d bytes → %d chars (lang=%s, prob=%.2f)",
            len(audio_bytes), len(result), info.language, info.language_probability,
        )
        return result
    except Exception:
        logger.exception("Whisper transcription failed")
        return ""
    finally:
        try:
            Path(tmp_path).unlink(missing_ok=True)
        except OSError:
            pass
