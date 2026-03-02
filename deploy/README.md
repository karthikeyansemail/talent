# Nalam Pulse — Deployment Package

Self-hosted deployment resources for [Nalam Pulse](https://nalampulse.com).
This repo contains everything needed to run Nalam Pulse on your own infrastructure.

## Quick Deploy

| Provider | Button | What it does |
|----------|--------|-------------|
| **AWS** | [Launch Stack →](https://console.aws.amazon.com/cloudformation/home#/stacks/create/review?templateURL=https://raw.githubusercontent.com/nalampulse/deploy/main/cloudformation/nalampulse-stack.yaml&stackName=NalamPulse) | CloudFormation creates EC2 + Docker + SSL automatically |
| **Azure** | [Deploy to Azure →](https://portal.azure.com/#create/Microsoft.Template/uri/https%3A%2F%2Fraw.githubusercontent.com%2Fnalampulse%2Fdeploy%2Fmain%2Farm%2Fazuredeploy.json) | ARM Template creates VM + Docker + SSL automatically |
| **GCP** | [Open in Cloud Shell →](https://console.cloud.google.com/cloudshell/open?git_repo=https://github.com/nalampulse/deploy&tutorial=gcp/tutorial.md&shellonly=true) | Interactive script creates VM via gcloud |
| **Any Linux VPS** | See below | Ubuntu 22.04, run one command |

### Generic VPS (DigitalOcean, Hetzner, Linode, etc.)

```bash
# Set these environment variables first:
export NALAM_LICENSE="your-license-key"
export NALAM_DOMAIN="pulse.yourcompany.com"
export NALAM_SMTP_HOST="smtp.gmail.com"
export NALAM_SMTP_USER="you@gmail.com"
export NALAM_SMTP_PASS="your-app-password"
export NALAM_AI_KEY="sk-..."

# Then run:
curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | sudo bash
```

The installer handles: Docker installation, SSL certificate (Let's Encrypt), and starting all 5 services.

## What's included

```
deploy/
├── cloudformation/
│   └── nalampulse-stack.yaml   # AWS CloudFormation template
├── arm/
│   └── azuredeploy.json        # Azure ARM template
├── gcp/
│   ├── deploy.sh               # GCP interactive deployment script
│   └── tutorial.md             # Cloud Shell tutorial
├── scripts/
│   └── install.sh              # Main installer (all providers)
├── docker-compose.yml          # Production Docker Compose
└── nginx.conf                  # Nginx reverse proxy config
```

## Prerequisites

Before deploying, have these ready:
- **License key** — from your subscription confirmation email
- **Domain name** — e.g. `pulse.yourcompany.com` (you'll add a DNS record after deploy)
- **SMTP credentials** — Gmail app password, Mailgun, or any SMTP provider
- **OpenAI API key** — for AI analysis features

## After deployment

1. Point your domain's A record to the server IP shown in the install output
2. Wait ~10 minutes for SSL certificate to be issued automatically
3. Open `https://your-domain.com`
4. Log in with the default credentials shown at install completion
5. **Change your password immediately**

## Support

- Docs: [nalampulse.com/docs](https://nalampulse.com/docs)
- Email: [support@nalampulse.com](mailto:support@nalampulse.com)
- Subscription required — [nalampulse.com/#pricing](https://nalampulse.com/#pricing)

## AI Service

The AI analysis engine is open source: [github.com/nalampulse/ai-service](https://github.com/nalampulse/ai-service)
