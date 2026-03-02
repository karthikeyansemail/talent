# Nalam Pulse — AI Service (Open Source)

This is the AI analysis engine powering [Nalam Pulse](https://nalampulse.com).
It is open source so customers can audit exactly how their employee data is processed by LLMs.

## What this service does

A stateless Python FastAPI service that receives structured data from the Laravel app and returns AI-generated analysis. It has **no database access** and **no persistent state**.

### Endpoints

| Endpoint | Input | Output |
|----------|-------|--------|
| `POST /analyze/resume` | Candidate resume text + job requirements | Score, strengths, gaps, recommendation |
| `POST /analyze/resource-match` | Employee skills + project requirements | Match score, fit analysis |
| `POST /analyze/work-pulse` | Employee task metrics (aggregated) | Narrative summary, engagement signals |
| `POST /health` | — | Service status |

## How data flows

```
Laravel App (private)
  → aggregates data from DB
  → strips PII where possible
  → POST to /analyze/*

AI Service (this repo)
  → constructs prompt (see prompts/)
  → calls OpenAI API
  → parses structured response
  → returns JSON to Laravel

Laravel App
  → stores result in DB
  → displays to HR manager
```

**The AI service never stores data.** Every request is stateless.

## Prompts

All prompts are in [`app/prompts/`](app/prompts/). You can read exactly what is sent to the LLM.

- [`resume_analysis.py`](app/prompts/resume_analysis.py) — resume scoring prompt
- [`resource_matching.py`](app/prompts/resource_matching.py) — employee-project matching
- [`work_pulse_analysis.py`](app/prompts/work_pulse_analysis.py) — work signal narrative

## Running locally

```bash
pip install -r requirements.txt
export OPENAI_API_KEY=sk-...
uvicorn app.main:app --reload --port 8000
```

## Docker

```bash
docker build -t nalampulse/ai-service .
docker run -p 8000:8000 -e OPENAI_API_KEY=sk-... nalampulse/ai-service
```

Or pull the published image:
```bash
docker pull nalampulse/ai-service:latest
```

## License

MIT — you can read, audit, fork, and self-host this service.
The main Nalam Pulse application is commercial software. See [nalampulse.com](https://nalampulse.com).
