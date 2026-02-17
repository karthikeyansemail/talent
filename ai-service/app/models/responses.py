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


class ResumeSignalResponse(BaseModel):
    """Resume signal extraction result — raw signals without overall score.

    The overall score is computed by the PHP ScoringEngine using configurable
    per-organisation weights, not by the AI service.
    """

    # Core signals (0-100)
    skill_match_score: float = Field(
        ..., ge=0, le=100, description="How well skills match requirements (0-100)"
    )
    experience_score: float = Field(
        ..., ge=0, le=100, description="Experience relevance and depth (0-100)"
    )
    relevance_score: float = Field(
        ..., ge=0, le=100, description="Job description relevance (0-100)"
    )
    authenticity_score: float = Field(
        ..., ge=0, le=100, description="Resume authenticity and consistency (0-100)"
    )

    # Quality / authenticity signals (0-100)
    keyword_density: float = Field(
        default=50, ge=0, le=100,
        description="100=natural language, 0=keyword stuffing",
    )
    generic_language: float = Field(
        default=50, ge=0, le=100,
        description="100=specific details, 0=all buzzwords",
    )
    verifiable_evidence: float = Field(
        default=50, ge=0, le=100,
        description="Verifiable companies, dates, metrics (0-100)",
    )
    career_progression: float = Field(
        default=50, ge=0, le=100,
        description="Logical career trajectory (0-100)",
    )
    quantified_claims: float = Field(
        default=50, ge=0, le=100,
        description="Measurable achievements present (0-100)",
    )

    # Qualitative data (unchanged from original analysis)
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
        ..., description="Detailed explanation of the assessment"
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


class JobParsingResponse(BaseModel):
    """Structured fields extracted from a job description document."""

    title: str = Field(default="", description="Job title")
    description: str = Field(default="", description="High-level role overview and purpose")
    key_responsibilities: str = Field(
        default="", description="Specific day-to-day duties and responsibilities"
    )
    requirements: str = Field(default="", description="Qualifications and requirements")
    expectations: str = Field(
        default="", description="Performance expectations and success criteria"
    )
    required_skills: list[str] = Field(
        default_factory=list, description="Required technical skills"
    )
    nice_to_have_skills: list[str] = Field(
        default_factory=list, description="Preferred / nice-to-have skills"
    )
    skill_experience_details: str = Field(
        default="",
        description="Per-skill experience requirements, e.g. 'React: 3-5 years'",
    )
    min_experience: int = Field(default=0, ge=0, description="Minimum years of experience")
    max_experience: int = Field(default=10, ge=0, description="Maximum years of experience")
    employment_type: str = Field(
        default="full_time",
        description="Employment type: full_time, part_time, contract, intern",
    )
    location: str = Field(default="", description="Job location")
    salary_min: float | None = Field(default=None, description="Minimum salary")
    salary_max: float | None = Field(default=None, description="Maximum salary")


class ResumeProfileResponse(BaseModel):
    """Candidate profile fields extracted from a resume."""

    first_name: str = Field(default="", description="Candidate first name")
    last_name: str = Field(default="", description="Candidate last name")
    email: str = Field(default="", description="Candidate email address")
    phone: str = Field(default="", description="Candidate phone number")
    current_company: str = Field(default="", description="Current employer")
    current_title: str = Field(default="", description="Current job title")
    experience_years: int | None = Field(
        default=None, description="Total years of professional experience"
    )
    skills: list[str] = Field(
        default_factory=list, description="Key technical and professional skills"
    )
    summary: str = Field(default="", description="Brief professional summary")


class ProjectParsingResponse(BaseModel):
    """Structured fields extracted from a project requirement document."""

    name: str = Field(default="", description="Project name")
    description: str = Field(default="", description="Project description / objectives / scope")
    required_skills: list[str] = Field(
        default_factory=list, description="Required technical skills"
    )
    required_technologies: list[str] = Field(
        default_factory=list, description="Required tools, frameworks, platforms"
    )
    complexity_level: str = Field(
        default="medium",
        description="Project complexity: low, medium, high, critical",
    )
    domain_context: str = Field(default="", description="Industry / business domain")
    start_date: str | None = Field(default=None, description="Start date (YYYY-MM-DD)")
    end_date: str | None = Field(default=None, description="End date (YYYY-MM-DD)")


class SignalAnalysisResponse(BaseModel):
    """AI-computed meta-signals from raw performance data."""

    consistency_index: float = Field(
        default=50, ge=0, le=100, description="Output consistency score (0-100)"
    )
    recovery_signal: float = Field(
        default=50, ge=0, le=100, description="Recovery speed after performance dips (0-100)"
    )
    workload_pressure: float = Field(
        default=50, ge=0, le=100, description="Workload pressure indicator (0-100)"
    )
    context_switching_index: float = Field(
        default=50, ge=0, le=100, description="Task/project switching frequency (0-100)"
    )
    collaboration_density: float = Field(
        default=50, ge=0, le=100, description="Cross-team collaboration level (0-100)"
    )
    summary: str = Field(
        default="", description="Brief objective summary of the analysis"
    )
    signal_insights: list[str] = Field(
        default_factory=list, description="Objective observations about the data"
    )
