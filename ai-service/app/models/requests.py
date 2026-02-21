from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field


class ResumeAnalysisRequest(BaseModel):
    """Request payload for resume analysis."""

    resume_text: str = Field(..., description="Full text content of the resume")
    job_title: str = Field(..., description="Target job title")
    job_description: str = Field(..., description="Full job description text")
    required_skills: list[str] = Field(
        default_factory=list, description="List of skills required for the role"
    )
    min_experience: int = Field(
        default=0, ge=0, description="Minimum years of experience required"
    )
    max_experience: int = Field(
        default=10, ge=0, description="Maximum years of experience expected"
    )


class JiraTask(BaseModel):
    """A single Jira task record."""

    key: str = Field(..., description="Jira issue key, e.g. PROJ-123")
    summary: str = Field(..., description="Issue summary / title")
    description: str = Field(default="", description="Issue description body")
    type: str = Field(default="Task", description="Issue type: Task, Story, Bug, etc.")
    status: str = Field(default="Done", description="Current status of the issue")
    priority: str = Field(default="Medium", description="Priority level")
    labels: list[str] = Field(default_factory=list, description="Issue labels")
    story_points: float | None = Field(
        default=None, description="Story point estimate"
    )
    resolved_at: str | None = Field(
        default=None, description="ISO-8601 resolution date"
    )


class JiraSignalRequest(BaseModel):
    """Request payload for Jira signal extraction."""

    employee_name: str = Field(..., description="Name of the employee")
    tasks: list[JiraTask] = Field(
        ..., description="List of Jira tasks completed by the employee"
    )


class ProjectRequirement(BaseModel):
    """Description of a project and its resource needs."""

    name: str = Field(..., description="Project name")
    description: str = Field(default="", description="Project description")
    required_skills: list[str] = Field(
        default_factory=list, description="Skills required for the project"
    )
    required_technologies: list[str] = Field(
        default_factory=list, description="Technologies used in the project"
    )
    complexity_level: str = Field(
        default="medium",
        description="Project complexity: low, medium, high, critical",
    )
    domain_context: str = Field(
        default="", description="Industry or domain context for the project"
    )


class EmployeeProfile(BaseModel):
    """Aggregated employee profile for resource matching."""

    id: int = Field(..., description="Employee ID")
    name: str = Field(..., description="Employee name")
    skills_from_resume: Any = Field(
        default=None,
        description="Skills extracted from resume analysis (list or dict)",
    )
    skills_from_jira: Any = Field(
        default=None,
        description="Skills inferred from Jira activity (list or dict)",
    )
    combined_skill_profile: Any = Field(
        default=None,
        description="Merged skill profile from all sources (list or dict)",
    )


class SprintSheetSummary(BaseModel):
    """Summary of an uploaded sprint spreadsheet for AI context."""

    filename: str = Field(default="", description="Original filename")
    summary: dict = Field(default_factory=dict, description="Parsed sprint data summary")


class ResourceMatchRequest(BaseModel):
    """Request payload for project-resource matching."""

    project: ProjectRequirement = Field(
        ..., description="Project requirements to match against"
    )
    employees: list[EmployeeProfile] = Field(
        ..., description="Candidate employee profiles"
    )
    sprint_data: list[SprintSheetSummary] = Field(
        default_factory=list,
        description="Optional sprint spreadsheet summaries for additional context",
    )


class JobParsingRequest(BaseModel):
    """Request payload for parsing a job description document."""

    document_text: str = Field(
        ..., description="Full text extracted from a job description document"
    )


class ResumeProfileRequest(BaseModel):
    """Request payload for extracting candidate profile from a resume."""

    resume_text: str = Field(
        ..., description="Full text extracted from a resume document"
    )


class ProjectParsingRequest(BaseModel):
    """Request payload for parsing a project requirement document."""

    document_text: str = Field(
        ..., description="Full text extracted from a project requirement document"
    )


class SignalAnalysisRequest(BaseModel):
    """Request payload for analyzing employee performance signals."""

    employee_name: str = Field(..., description="Name of the employee")
    period: str = Field(..., description="Time period, e.g. 2026-W06")
    signals: list[dict] = Field(
        ..., description="List of raw signal data points"
    )
