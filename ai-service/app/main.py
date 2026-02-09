"""FastAPI application entry-point for the Talent Intelligence AI Service."""

from __future__ import annotations

import logging

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware

from app.config import settings
from app.models.requests import (
    ResumeAnalysisRequest,
    JiraSignalRequest,
    ResourceMatchRequest,
)
from app.models.responses import (
    ResumeAnalysisResponse,
    JiraSignalResponse,
    ResourceMatchResponse,
)
from app.services.resume_analyzer import analyze_resume
from app.services.jira_signal_extractor import extract_jira_signals
from app.services.resource_matcher import match_project_resources

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
