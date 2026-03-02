@extends('layouts.app')
@section('title', 'Self-Hosted Deployment Guide')
@section('page-title', 'Billing & Plan')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('settings.billing.index') }}" class="btn btn-secondary" style="padding:6px 12px;font-size:13px">← Back to Billing</a>
        <h1>Self-Hosted Deployment Guide</h1>
    </div>
</div>

{{-- Intro banner --}}
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--primary)">
    <div class="card-body" style="padding:16px 20px">
        <div style="font-size:14px;font-weight:600;color:var(--gray-800);margin-bottom:4px">Deploy Nalam Pulse on your own infrastructure</div>
        <div style="font-size:13px;color:var(--gray-500)">
            Choose your cloud provider below. Each guide walks you through creating a VM, installing Docker, and launching the full stack in under 30 minutes.
            All options use the same <strong>Docker Compose</strong> package — only the VM creation step differs.
        </div>
    </div>
</div>

{{-- Prerequisites --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span>Before You Begin</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            @foreach([
                ['License Key', 'You received this by email after purchase. Keep it handy — you\'ll enter it during setup.'],
                ['Domain/IP', 'A domain name (e.g. talent.yourcompany.com) or static IP for your VM. Needed for SSL.'],
                ['SMTP Credentials', 'For sending email notifications. Gmail App Password, Mailgun, or any SMTP provider works.'],
            ] as [$title, $desc])
            <div style="padding:14px;background:var(--gray-50);border-radius:8px">
                <div style="font-size:13px;font-weight:600;color:var(--gray-800);margin-bottom:4px">✓ {{ $title }}</div>
                <div style="font-size:12px;color:var(--gray-500)">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Quick Deploy Buttons --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span>Quick Deploy — One Click to Launch</span></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px">
            Each button opens your cloud provider's console with a <strong>pre-filled infrastructure template</strong>. You fill in a short form (license key, domain, SMTP, AI key) and click Create — the VM is provisioned, Docker installed, and the app started automatically. Have your credentials ready.
        </p>
        <div style="display:flex;gap:12px;flex-wrap:wrap">

            {{-- AWS CloudFormation --}}
            {{-- templateURL must be publicly accessible; GitHub raw URL works if repo is public --}}
            @php
            $cfTemplate  = urlencode('https://raw.githubusercontent.com/nalampulse/deploy/main/cloudformation/nalampulse-stack.yaml');
            $armTemplate = urlencode('https://raw.githubusercontent.com/nalampulse/deploy/main/arm/azuredeploy.json');
            @endphp
            <a href="https://console.aws.amazon.com/cloudformation/home#/stacks/create/review?templateURL={{ $cfTemplate }}&stackName=NalamPulse"
               target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:10px;padding:12px 20px;background:#FF9900;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:opacity .15s"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.527 17.176l-2.287 2.456c-.163.176-.353.268-.54.268-.104 0-.207-.03-.3-.092l-.046-.032c-.437-.304-.657-.884-.53-1.42l.308-1.32H8.5c-.47 0-.888-.26-1.08-.676-.192-.416-.123-.893.178-1.24l5.472-6.304a.6.6 0 0 1 .498-.21c.295.02.553.198.677.466l1.51 3.264h1.742c.47 0 .888.26 1.08.675.192.416.123.893-.178 1.24l-4.872 3.925z" fill="white"/></svg>
                Deploy to AWS
                <span style="font-size:11px;opacity:.85;font-weight:400">CloudFormation</span>
            </a>

            {{-- GCP Cloud Shell — clones repo and runs deploy.sh interactively --}}
            <a href="https://console.cloud.google.com/cloudshell/open?git_repo=https://github.com/nalampulse/deploy&tutorial=gcp/tutorial.md&shellonly=true"
               target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:10px;padding:12px 20px;background:#4285F4;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:opacity .15s"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 8l-4 4 4 4M18 8l4 4-4 4M14 4l-4 16" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Deploy to GCP
                <span style="font-size:11px;opacity:.85;font-weight:400">Cloud Shell</span>
            </a>

            {{-- Azure ARM Template --}}
            <a href="https://portal.azure.com/#create/Microsoft.Template/uri/{{ $armTemplate }}"
               target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:10px;padding:12px 20px;background:#0078D4;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:opacity .15s"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 3L5 14h7l-1 7 9-11h-7l1-7z" fill="white"/></svg>
                Deploy to Azure
                <span style="font-size:11px;opacity:.85;font-weight:400">ARM Template</span>
            </a>

            {{-- Generic VPS — copy-paste installer --}}
            <button onclick="document.getElementById('vps-cmd').style.display='block';this.style.display='none'"
               style="display:inline-flex;align-items:center;gap:10px;padding:12px 20px;background:#374151;color:#fff;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="3" width="20" height="14" rx="2" stroke="white" stroke-width="1.5"/><path d="M8 21h8M12 17v4" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                Any Linux VPS
                <span style="font-size:11px;opacity:.85;font-weight:400">Copy installer</span>
            </button>
        </div>

        {{-- VPS one-liner (shown on click) --}}
        <div id="vps-cmd" style="display:none;margin-top:12px;background:#0f172a;border-radius:8px;padding:14px 16px">
            <div style="font-size:11px;color:#94a3b8;margin-bottom:6px">Run this on any fresh Ubuntu 22.04 server as root or with sudo:</div>
            <code style="font-family:monospace;font-size:13px;color:#86efac">curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | sudo bash</code>
            <div style="font-size:11px;color:#64748b;margin-top:6px">Set NALAM_LICENSE, NALAM_DOMAIN, NALAM_SMTP_HOST, NALAM_SMTP_USER, NALAM_SMTP_PASS, NALAM_AI_KEY environment variables before running, or the script will prompt you.</div>
        </div>

        <div style="margin-top:14px;padding:10px 14px;background:#f0f9ff;border-radius:6px;border-left:3px solid #38bdf8">
            <div style="font-size:12px;color:#0369a1;font-weight:600;margin-bottom:2px">What happens after you click</div>
            <div style="font-size:12px;color:#0c4a6e">
                AWS &amp; Azure: Fill a short parameters form (license key, domain, SMTP, AI key) → click Create → VM + Docker + app start automatically. Only thing left: point your DNS to the output IP.<br>
                GCP: Cloud Shell opens with repo cloned → run <code>bash deploy/gcp/deploy.sh</code> → it asks the same questions and creates the VM for you.
            </div>
        </div>
    </div>
</div>

{{-- Tab navigation --}}
<div style="display:flex;gap:4px;margin-bottom:0;border-bottom:2px solid var(--gray-200)">
    @foreach([
        ['aws',   'Amazon Web Services', 'EC2 + Docker'],
        ['gcp',   'Google Cloud',         'Compute Engine + Docker'],
        ['azure', 'Microsoft Azure',      'Azure VM + Docker'],
        ['vps',   'Generic Linux VPS',    'Any Ubuntu 22.04 server'],
    ] as [$id, $label, $sub])
    <button onclick="switchTab('{{ $id }}')" id="tab-{{ $id }}"
            style="padding:10px 20px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:var(--gray-500);border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s">
        {{ $label }}
        <div style="font-size:11px;font-weight:400;color:var(--gray-400)">{{ $sub }}</div>
    </button>
    @endforeach
</div>

{{-- ── AWS Tab ──────────────────────────────────────────────────── --}}
<div id="pane-aws" class="deploy-pane" style="display:none">
    <div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:20px;color:var(--gray-800)">Deploy on Amazon EC2</h3>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Launch an EC2 Instance', 'content'=>'
<p>Go to <strong>EC2 → Launch Instance</strong>.</p>
<ul>
<li>AMI: <strong>Ubuntu 22.04 LTS</strong></li>
<li>Instance type: <strong>t3.medium</strong> (2 vCPU, 4 GB RAM) minimum. <strong>t3.large</strong> recommended for production.</li>
<li>Storage: <strong>30 GB gp3</strong></li>
<li>Security group: open ports <strong>80</strong> (HTTP), <strong>443</strong> (HTTPS), <strong>22</strong> (SSH)</li>
<li>Create or select a key pair (.pem file) for SSH access</li>
</ul>
<p>After launch, note the <strong>Public IPv4 address</strong> (or assign an Elastic IP for a stable address).</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Point Your Domain', 'content'=>'
<p>In your DNS provider, create an <strong>A record</strong>:</p>
<pre>talent.yourcompany.com  →  &lt;EC2 Public IP&gt;</pre>
<p>Wait for propagation (usually 5–15 minutes). If you don\'t have a domain yet, you can use the raw IP first and add SSL later.</p>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'SSH into Your Instance', 'content'=>'
<pre>chmod 400 your-key.pem
ssh -i your-key.pem ubuntu@&lt;EC2-PUBLIC-IP&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Run the One-Line Installer', 'content'=>'
<p>Once logged in, run:</p>
<pre>curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | sudo bash</pre>
<p>This script will:
<ul>
<li>Install Docker + Docker Compose</li>
<li>Download the Nalam Pulse package</li>
<li>Prompt you for your <strong>license key</strong>, domain, SMTP, and AI API key</li>
<li>Start all 5 containers (app, nginx, db, ai-service, queue-worker)</li>
<li>Obtain a free <strong>Let\'s Encrypt SSL certificate</strong> via Certbot</li>
</ul>
</p>
<p>Total time: ~10 minutes on a fresh server.</p>
'])

            @include('settings._deploy-step', ['n'=>5, 'title'=>'Verify and Access', 'content'=>'
<p>After the installer completes, open your domain in a browser. You should see the Nalam Pulse login screen.</p>
<p>Default super admin credentials are shown at the end of the installer output. <strong>Change your password immediately after first login.</strong></p>
<p>To check container status at any time:</p>
<pre>cd /opt/nalampulse && docker compose ps</pre>
'])

            @include('settings._deploy-step', ['n'=>6, 'title'=>'Backups (Recommended)', 'content'=>'
<p>Enable <strong>AWS Automated Backups</strong> or add a daily cron to dump the MySQL database:</p>
<pre>0 2 * * * docker exec nalampulse-db mysqldump -u root talent_db | gzip > /backups/talent_$(date +\%F).sql.gz</pre>
<p>Also snapshot your EBS volume weekly from the EC2 console.</p>
'])
        </div>
    </div>
</div>

{{-- ── GCP Tab ──────────────────────────────────────────────────── --}}
<div id="pane-gcp" class="deploy-pane" style="display:none">
    <div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:20px;color:var(--gray-800)">Deploy on Google Compute Engine</h3>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Create a VM Instance', 'content'=>'
<p>Go to <strong>Compute Engine → VM Instances → Create Instance</strong>.</p>
<ul>
<li>Machine type: <strong>e2-medium</strong> (2 vCPU, 4 GB) minimum. <strong>e2-standard-2</strong> for production.</li>
<li>Boot disk: <strong>Ubuntu 22.04 LTS</strong>, 30 GB standard disk</li>
<li>Firewall: check <strong>Allow HTTP traffic</strong> and <strong>Allow HTTPS traffic</strong></li>
<li>Region: choose closest to your team</li>
</ul>
<p>Note the <strong>External IP</strong>. Reserve it as a static address: VPC Network → External IP → Reserve.</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Point Your Domain', 'content'=>'
<p>Create an <strong>A record</strong> in your DNS pointing to the static external IP:</p>
<pre>talent.yourcompany.com  →  &lt;GCP External IP&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'SSH into Your VM', 'content'=>'
<p>Use the GCP Console SSH button, or from gcloud CLI:</p>
<pre>gcloud compute ssh INSTANCE_NAME --zone=ZONE</pre>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Run the One-Line Installer', 'content'=>'
<pre>curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | sudo bash</pre>
<p>Follow the prompts: license key, domain, SMTP, AI key. The installer handles Docker installation and SSL automatically.</p>
'])

            @include('settings._deploy-step', ['n'=>5, 'title'=>'Verify and Access', 'content'=>'
<p>Visit your domain in a browser. Check containers:</p>
<pre>cd /opt/nalampulse && docker compose ps</pre>
<p>All 5 services should show status <strong>Up</strong>.</p>
'])

            @include('settings._deploy-step', ['n'=>6, 'title'=>'Backups', 'content'=>'
<p>Enable <strong>Scheduled Snapshots</strong> on your persistent disk from the GCP Console (Compute Engine → Disks → Create Snapshot Schedule).</p>
<p>For database-level backups, add the same cron as the AWS guide above.</p>
'])
        </div>
    </div>
</div>

{{-- ── Azure Tab ─────────────────────────────────────────────────── --}}
<div id="pane-azure" class="deploy-pane" style="display:none">
    <div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:20px;color:var(--gray-800)">Deploy on Azure Virtual Machine</h3>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Create a Virtual Machine', 'content'=>'
<p>Go to <strong>Azure Portal → Virtual Machines → Create</strong>.</p>
<ul>
<li>Image: <strong>Ubuntu Server 22.04 LTS</strong></li>
<li>Size: <strong>Standard_B2s</strong> (2 vCPU, 4 GB) minimum. <strong>Standard_B2ms</strong> for production.</li>
<li>Authentication: SSH public key (generate or use existing)</li>
<li>Inbound ports: allow <strong>HTTP (80)</strong>, <strong>HTTPS (443)</strong>, <strong>SSH (22)</strong></li>
<li>OS disk: 32 GB Standard SSD</li>
</ul>
<p>After creation, go to the VM → <strong>DNS name label</strong> and assign a DNS name, or use the Public IP and set an A record.</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Point Your Domain', 'content'=>'
<p>In your DNS provider, create an <strong>A record</strong> to the VM\'s public IP. Or use the Azure-assigned DNS name directly:</p>
<pre>talent.yourcompany.com  →  &lt;Azure Public IP&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'SSH into Your VM', 'content'=>'
<pre>ssh -i ~/.ssh/your-key.pem azureuser@&lt;PUBLIC-IP&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Run the One-Line Installer', 'content'=>'
<pre>curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | sudo bash</pre>
<p>Follow the prompts. The installer detects Ubuntu and installs Docker automatically.</p>
'])

            @include('settings._deploy-step', ['n'=>5, 'title'=>'Verify and Access', 'content'=>'
<p>Visit your domain. Check all containers:</p>
<pre>cd /opt/nalampulse && docker compose ps</pre>
'])

            @include('settings._deploy-step', ['n'=>6, 'title'=>'Backups', 'content'=>'
<p>Enable <strong>Azure Backup</strong> for the VM disk from the VM blade → Backup. For database-level backups, set up the daily cron in the crontab of the VM.</p>
'])
        </div>
    </div>
</div>

{{-- ── Generic VPS Tab ──────────────────────────────────────────── --}}
<div id="pane-vps" class="deploy-pane" style="display:none">
    <div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:20px;color:var(--gray-800)">Deploy on Any Linux VPS (DigitalOcean, Hetzner, Linode, etc.)</h3>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Provision a VPS', 'content'=>'
<ul>
<li>OS: <strong>Ubuntu 22.04 LTS</strong></li>
<li>RAM: minimum <strong>4 GB</strong> (8 GB recommended)</li>
<li>CPU: minimum <strong>2 vCPU</strong></li>
<li>Disk: minimum <strong>30 GB SSD</strong></li>
</ul>
<p>Open firewall ports: <strong>22</strong>, <strong>80</strong>, <strong>443</strong>.</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Point Your Domain', 'content'=>'
<pre>talent.yourcompany.com  →  &lt;VPS Public IP&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'SSH and Run Installer', 'content'=>'
<pre>ssh root@&lt;VPS-IP&gt;
curl -fsSL https://raw.githubusercontent.com/nalampulse/deploy/main/scripts/install.sh | sudo bash</pre>
<p>Enter your license key, domain, SMTP details, and AI API key when prompted.</p>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Verify', 'content'=>'
<pre>cd /opt/nalampulse && docker compose ps</pre>
<p>All 5 services (app, nginx, db, ai-service, queue-worker) should be running.</p>
'])
        </div>
    </div>
</div>

{{-- Support footer --}}
<div class="card" style="margin-top:20px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px">
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--gray-800)">Need help with deployment?</div>
            <div style="font-size:12px;color:var(--gray-500)">Our team offers paid white-glove setup assistance. We can provision and configure everything for you.</div>
        </div>
        <a href="mailto:support@nalampulse.com?subject=Self-Hosted%20Setup%20Assistance" class="btn btn-primary">
            Request Setup Assistance
        </a>
    </div>
</div>

@endsection

@push('scripts')
<script>
function switchTab(id) {
    document.querySelectorAll('.deploy-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('[id^="tab-"]').forEach(t => {
        t.style.color = 'var(--gray-500)';
        t.style.borderBottomColor = 'transparent';
    });
    document.getElementById('pane-' + id).style.display = 'block';
    const btn = document.getElementById('tab-' + id);
    btn.style.color = 'var(--primary)';
    btn.style.borderBottomColor = 'var(--primary)';
}
document.addEventListener('DOMContentLoaded', () => switchTab('aws'));
</script>
@endpush
