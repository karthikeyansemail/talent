# Deploy Nalam Pulse on GCP

Welcome to the Nalam Pulse GCP deployment guide. Cloud Shell already has the deployment scripts ready.

## Before you start, have these ready:
- Your **Nalam Pulse license key** (from subscription email)
- Your **domain name** (e.g. `pulse.yourcompany.com`)
- **SMTP credentials** (Gmail app password, Mailgun, or any SMTP provider)
- Your **OpenAI API key** (`sk-...`)

## Step 1 — Run the deployment script

```bash
bash deploy/gcp/deploy.sh
```

The script will ask you:
- GCP Project ID and zone
- VM name and machine type
- License key, domain, SMTP, and AI key

It then creates the VM and starts the automated installer.

## Step 2 — Add a DNS record

After the script completes, it shows you a static IP. Add an **A record** in your DNS:

```
pulse.yourcompany.com  →  <IP shown above>
```

## Step 3 — Wait ~10 minutes

The installer runs Docker setup + SSL in the background. You can monitor it:

```bash
gcloud compute ssh nalam-pulse --zone=<your-zone> -- tail -f /var/log/nalampulse-install.log
```

## Step 4 — Open your browser

Visit `https://your-domain.com`

Default login: `admin@acme.com` / `password` — **change this immediately**.

---

That's it. Three real steps: run script → add DNS → login.
