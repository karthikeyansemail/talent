from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    """Application settings loaded from environment variables and .env file."""

    llm_provider: str = "openai"  # "openai" or "anthropic"
    openai_api_key: str = ""
    openai_model: str = "gpt-4o"
    anthropic_api_key: str = ""
    anthropic_model: str = "claude-sonnet-4-20250514"
    ai_service_host: str = "0.0.0.0"
    ai_service_port: int = 8000
    log_level: str = "info"

    class Config:
        env_file = ".env"


settings = Settings()
