from __future__ import annotations

from pydantic import BaseModel, Field


class SkillAnalysis(BaseModel):
    """Analysis of a single skill found in a resume."""

    skill: str = Field(..., description="Skill name")
    level: str = Field(
        ..., description="Proficiency level: beginner, intermediate, advanced, expert"
    )
    evidence: str = Field(
        ..., description="Evidence from the resume supporting this assessment"
    )
    score: float = Field(
        ..., ge=0, le=100, description="Skill proficiency score (0-100)"
    )


class ResumeAnalysisResponse(BaseModel):
    """Complete resume analysis result."""

    overall_score: float = Field(
        ..., ge=0, le=100, description="Weighted overall match score (0-100)"
    )
    skill_match_score: float = Field(
        ..., ge=0, le=100, description="How well skills match requirements (0-100)"
    )
    experience_score: float = Field(
        ..., ge=0, le=100, description="Experience relevance and depth score (0-100)"
    )
    relevance_score: float = Field(
        ...,
        ge=0,
        le=100,
        description="Job description relevance score (0-100)",
    )
    authenticity_score: float = Field(
        ...,
        ge=0,
        le=100,
        description="Resume authenticity and consistency score (0-100)",
    )
    skill_analysis: list[SkillAnalysis] = Field(
        default_factory=list, description="Per-skill breakdown"
    )
    experience_summary: str = Field(
        ..., description="Narrative summary of candidate experience"
    )
    strengths: list[str] = Field(
        default_factory=list, description="Key strengths identified"
    )
    concerns: list[str] = Field(
        default_factory=list, description="Potential red flags or concerns"
    )
    recommendation: str = Field(
        ...,
        description="Overall recommendation: strong_match, good_match, partial_match, weak_match",
    )
    explanation: str = Field(
        ..., description="Detailed explanation of the recommendation"
    )


class ExtractedSkill(BaseModel):
    """A skill inferred from Jira task history."""

    skill: str = Field(..., description="Skill name")
    confidence: float = Field(
        ..., ge=0, le=1, description="Confidence that this skill is present (0-1)"
    )
    depth: str = Field(
        ..., description="Inferred depth: surface, working, deep, expert"
    )
    evidence_count: int = Field(
        ..., ge=0, description="Number of tasks providing evidence for this skill"
    )
    last_used: str = Field(
        default="", description="Approximate date the skill was last demonstrated"
    )


class WorkPatterns(BaseModel):
    """Behavioral work patterns inferred from Jira activity."""

    complexity_preference: str = Field(
        ..., description="Preferred task complexity: low, medium, high, mixed"
    )
    avg_story_points: float = Field(
        ..., ge=0, description="Average story points per completed task"
    )
    domains: list[str] = Field(
        default_factory=list, description="Technical or business domains worked in"
    )
    consistency_score: float = Field(
        ...,
        ge=0,
        le=100,
        description="Delivery consistency score (0-100)",
    )


class JiraSignalResponse(BaseModel):
    """Result of Jira signal extraction for an employee."""

    extracted_skills: list[ExtractedSkill] = Field(
        default_factory=list, description="Skills inferred from Jira tasks"
    )
    work_patterns: WorkPatterns = Field(
        ..., description="Inferred work behaviour patterns"
    )
    summary: str = Field(
        ..., description="Narrative summary of employee capabilities"
    )


class EmployeeMatch(BaseModel):
    """Match result for a single employee against project requirements."""

    employee_id: int = Field(..., description="Employee ID")
    match_score: float = Field(
        ..., ge=0, le=100, description="Overall match score (0-100)"
    )
    strength_areas: list[str] = Field(
        default_factory=list,
        description="Areas where the employee excels for this project",
    )
    skill_gaps: list[str] = Field(
        default_factory=list,
        description="Skills the employee lacks for this project",
    )
    explanation: str = Field(
        ..., description="Reasoning behind the match score"
    )


class ResourceMatchResponse(BaseModel):
    """Result of matching employees to a project."""

    matches: list[EmployeeMatch] = Field(
        default_factory=list,
        description="Ranked list of employee matches",
    )
