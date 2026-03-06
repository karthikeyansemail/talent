"""FastAPI application entry-point for the Talent Intelligence AI Service."""

from __future__ import annotations

import logging

from fastapi import FastAPI, HTTPException, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware

from app.config import settings
from app.models.requests import (
    ResumeAnalysisRequest,
    JiraSignalRequest,
    ResourceMatchRequest,
    JobParsingRequest,
    ResumeProfileRequest,
    ProjectParsingRequest,
    SignalAnalysisRequest,
    WorkPulseAnalyzeRequest,
)
from app.models.responses import (
    ResumeAnalysisResponse,
    ResumeSignalResponse,
    JiraSignalResponse,
    ResourceMatchResponse,
    JobParsingResponse,
    ResumeProfileResponse,
    ProjectParsingResponse,
    SignalAnalysisResponse,
    WorkPulseInsightResponse,
)
from app.services.resume_analyzer import analyze_resume, extract_resume_signals
from app.services.jira_signal_extractor import extract_jira_signals
from app.services.resource_matcher import match_project_resources
from app.services.job_parser import parse_job_description
from app.services.resume_profile_parser import parse_resume_profile
from app.services.project_parser import parse_project_requirements
from app.services.signal_analyzer import analyze_signals
from app.services.work_pulse_analyzer import analyze_work_pulse
from app.services.interview_assistant import (
    generate_interview_questions,
    evaluate_interview_answer,
    generate_interview_summary,
)
from app.services.audio_transcriber import transcribe_audio

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
logging.basicConfig(
    level=getattr(logging, settings.log_level.upper(), logging.INFO),
    format="%(asctime)s  %(levelname)-8s  %(name)s  %(message)s",
)
logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Application
# ---------------------------------------------------------------------------
app = FastAPI(
    title="Talent Intelligence AI Service",
    description=(
        "AI-powered microservice that analyses resumes, extracts skill signals "
        "from Jira activity, and matches employees to project requirements."
    ),
    version="1.0.0",
)

# ---------------------------------------------------------------------------
# CORS -- allow all origins during development
# ---------------------------------------------------------------------------
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------


@app.get("/health", tags=["system"])
async def health_check() -> dict:
    """Lightweight liveness / readiness probe."""
    return {"status": "healthy", "version": "1.0.0"}


@app.post(
    "/analyze-resume",
    response_model=ResumeAnalysisResponse,
    tags=["analysis"],
    summary="Analyse a resume against a job description",
)
async def analyze_resume_endpoint(request: ResumeAnalysisRequest) -> ResumeAnalysisResponse:
    """Score and evaluate a candidate resume against the supplied job requirements.

    Returns a structured breakdown of skill match, experience relevance,
    authenticity, and an overall recommendation.
    """
    try:
        return await analyze_resume(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /analyze-resume")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/extract-resume-signals",
    response_model=ResumeSignalResponse,
    tags=["analysis"],
    summary="Extract raw resume signals without computing an overall score",
)
async def extract_resume_signals_endpoint(request: ResumeAnalysisRequest) -> ResumeSignalResponse:
    """Extract 9 measurable signals from a resume for configurable scoring.

    Unlike /analyze-resume, this endpoint does NOT return an overall_score.
    The PHP application computes the final score using per-organisation weights.
    """
    try:
        return await extract_resume_signals(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /extract-resume-signals")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/extract-jira-signals",
    response_model=JiraSignalResponse,
    tags=["analysis"],
    summary="Extract skill signals from Jira task history",
)
async def extract_jira_signals_endpoint(request: JiraSignalRequest) -> JiraSignalResponse:
    """Infer employee skills, technical depth, and work patterns from
    their completed Jira tasks.
    """
    try:
        return await extract_jira_signals(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /extract-jira-signals")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/match-project-resources",
    response_model=ResourceMatchResponse,
    tags=["analysis"],
    summary="Match employees to project requirements",
)
async def match_project_resources_endpoint(request: ResourceMatchRequest) -> ResourceMatchResponse:
    """Rank a set of employees by their suitability for a project, considering
    skills from resumes and Jira activity.
    """
    try:
        return await match_project_resources(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /match-project-resources")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/parse-job-description",
    response_model=JobParsingResponse,
    tags=["parsing"],
    summary="Parse a job description document into structured fields",
)
async def parse_job_description_endpoint(request: JobParsingRequest) -> JobParsingResponse:
    """Extract structured job posting fields from an unstructured job description document."""
    try:
        return await parse_job_description(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /parse-job-description")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/parse-resume-profile",
    response_model=ResumeProfileResponse,
    tags=["parsing"],
    summary="Extract candidate profile fields from a resume",
)
async def parse_resume_profile_endpoint(request: ResumeProfileRequest) -> ResumeProfileResponse:
    """Extract biographical and professional fields from a resume for candidate profile creation."""
    try:
        return await parse_resume_profile(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /parse-resume-profile")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/parse-project-requirements",
    response_model=ProjectParsingResponse,
    tags=["parsing"],
    summary="Parse a project requirement document into structured fields",
)
async def parse_project_requirements_endpoint(request: ProjectParsingRequest) -> ProjectParsingResponse:
    """Extract structured project fields from an unstructured requirement document."""
    try:
        return await parse_project_requirements(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /parse-project-requirements")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/analyze-signals",
    response_model=SignalAnalysisResponse,
    tags=["intelligence"],
    summary="Analyze employee performance signals and compute meta-signals",
)
async def analyze_signals_endpoint(request: SignalAnalysisRequest) -> SignalAnalysisResponse:
    """Compute meta-signals (Consistency Index, Recovery Signal, etc.) from raw
    performance metrics. Returns only objective, measurable analysis."""
    try:
        return await analyze_signals(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /analyze-signals")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/work-pulse/analyze",
    response_model=WorkPulseInsightResponse,
    tags=["work-pulse"],
    summary="AI qualitative analysis of employee work patterns",
)
async def analyze_work_pulse_endpoint(request: WorkPulseAnalyzeRequest) -> WorkPulseInsightResponse:
    """Derive 5 qualitative work dimensions from an employee's task history.

    Returns direction labels (Strong / Solid / Developing / Inconsistent) and a
    management-ready narrative — no numerical scores.
    """
    try:
        return await analyze_work_pulse(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /work-pulse/analyze")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


# ---------------------------------------------------------------------------
# Interview Assistant
# ---------------------------------------------------------------------------


@app.post(
    "/generate-interview-questions",
    tags=["interview"],
    summary="Generate AI-resistant interview questions",
)
async def generate_interview_questions_endpoint(request: dict) -> dict:
    """Generate contextual interview questions based on job requirements,
    candidate profile, and conversation history."""
    try:
        return await generate_interview_questions(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /generate-interview-questions")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/evaluate-interview-answer",
    tags=["interview"],
    summary="Evaluate a candidate's answer to an interview question",
)
async def evaluate_interview_answer_endpoint(request: dict) -> dict:
    """Evaluate depth of understanding, practical experience, and communication."""
    try:
        return await evaluate_interview_answer(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /evaluate-interview-answer")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


@app.post(
    "/generate-interview-summary",
    tags=["interview"],
    summary="Generate a comprehensive interview summary",
)
async def generate_interview_summary_endpoint(request: dict) -> dict:
    """Summarize the entire interview with scores, strengths, concerns,
    and a hiring recommendation narrative."""
    try:
        return await generate_interview_summary(request)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:
        logger.exception("Unhandled error in /generate-interview-summary")
        raise HTTPException(status_code=500, detail="Internal AI processing error.") from exc


# ---------------------------------------------------------------------------
# Audio Transcription
# ---------------------------------------------------------------------------


@app.post(
    "/transcribe-audio",
    tags=["interview"],
    summary="Transcribe an audio chunk using local Whisper model",
)
async def transcribe_audio_endpoint(file: UploadFile = File(...)) -> dict:
    """Accept an audio file (webm/wav/mp3) and return transcribed text."""
    try:
        audio_bytes = await file.read()
        text = await transcribe_audio(audio_bytes)
        return {"text": text}
    except Exception as exc:
        logger.exception("Unhandled error in /transcribe-audio")
        raise HTTPException(status_code=500, detail="Transcription failed.") from exc


# ---------------------------------------------------------------------------
# Standalone runner (python -m app.main)
# ---------------------------------------------------------------------------
if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "app.main:app",
        host=settings.ai_service_host,
        port=settings.ai_service_port,
        reload=True,
        log_level=settings.log_level.lower(),
    )
