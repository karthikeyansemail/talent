from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    """Application settings loaded from environment variables and .env file."""

    llm_provider: str = "azure_openai"  # "openai", "anthropic", or "azure_openai"
    openai_api_key: str = ""
    openai_model: str = "gpt-4o"
    anthropic_api_key: str = ""
    anthropic_model: str = "claude-sonnet-4-20250514"
    # Azure OpenAI settings
    azure_openai_endpoint: str = ""
    azure_openai_api_key: str = ""
    azure_openai_deployment: str = ""
    azure_openai_api_version: str = "2024-08-01-preview"
    ai_service_host: str = "0.0.0.0"
    ai_service_port: int = 8000
    log_level: str = "info"

    class Config:
        env_file = ".env"


settings = Settings()
