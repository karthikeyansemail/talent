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
            Click a button below to deploy on your preferred cloud. AWS and Azure use pre-filled infrastructure templates — just fill a short form and click Create.
            GCP opens Cloud Shell with one command to run. All options are fully automated — no manual Docker setup or SSH required.
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
            <h3 style="font-size:15px;font-weight:700;margin-bottom:4px;color:var(--gray-800)">Deploy on Amazon EC2 via CloudFormation</h3>
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:20px">CloudFormation creates the VM, installs Docker, and starts all containers automatically. No SSH required.</p>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Click "Deploy to AWS" above', 'content'=>'
<p>The <strong>Deploy to AWS — CloudFormation</strong> button at the top of this page opens the AWS CloudFormation console with the Nalam Pulse infrastructure template pre-loaded.</p>
<p>You will land directly on the <strong>Create Stack → Review</strong> page — the template is already filled in.</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Fill in the Parameters form', 'content'=>'
<p>CloudFormation shows a short form. Fill in:</p>
<ul>
<li><strong>KeyPairName</strong> — an existing EC2 key pair (create one in EC2 → Key Pairs if you don\'t have one)</li>
<li><strong>NalamLicenseKey</strong> — the license key from your purchase email</li>
<li><strong>DomainName</strong> — e.g. <code>talent.yourcompany.com</code></li>
<li><strong>SMTPHost / SMTPPort / SMTPUser / SMTPPassword</strong> — your email provider credentials</li>
<li><strong>OpenAIApiKey</strong> — your OpenAI API key</li>
</ul>
<p>Instance type defaults to <strong>t3.medium</strong>. Change to <strong>t3.large</strong> for production workloads.</p>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'Click "Create Stack"', 'content'=>'
<p>Scroll to the bottom, check the acknowledgement checkbox, and click <strong>Create Stack</strong>.</p>
<p>CloudFormation will:</p>
<ul>
<li>Launch an EC2 instance (Ubuntu 22.04) with a static Elastic IP</li>
<li>Open ports 80, 443, and 22 automatically</li>
<li>Install Docker, pull all Nalam Pulse containers, and start them</li>
<li>Obtain a free Let\'s Encrypt SSL certificate for your domain</li>
</ul>
<p>Total time: <strong>~10 minutes</strong>. You can watch progress in the CloudFormation Events tab.</p>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Point your domain to the output IP', 'content'=>'
<p>Once the stack status shows <strong>CREATE_COMPLETE</strong>, click the <strong>Outputs</strong> tab. Copy the <strong>ElasticIP</strong> value.</p>
<p>In your DNS provider, create an A record:</p>
<pre>talent.yourcompany.com  →  &lt;ElasticIP from Outputs&gt;</pre>
<p>DNS propagation usually takes 5–15 minutes.</p>
'])

            @include('settings._deploy-step', ['n'=>5, 'title'=>'Access Nalam Pulse', 'content'=>'
<p>Visit <code>https://talent.yourcompany.com</code> in your browser. You should see the Nalam Pulse login screen.</p>
<p>Default super admin credentials are shown in the EC2 instance system log (<strong>EC2 → Actions → Monitor and troubleshoot → Get system log</strong>). <strong>Change your password immediately after first login.</strong></p>
'])
        </div>
    </div>
</div>

{{-- ── GCP Tab ──────────────────────────────────────────────────── --}}
<div id="pane-gcp" class="deploy-pane" style="display:none">
    <div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:4px;color:var(--gray-800)">Deploy on Google Compute Engine via Cloud Shell</h3>
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:20px">Cloud Shell opens with the deployment repo pre-cloned. One command creates the VM and starts all containers.</p>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Click "Deploy to GCP" above', 'content'=>'
<p>The <strong>Deploy to GCP — Cloud Shell</strong> button opens Google Cloud Shell in your browser with the <code>nalampulse/deploy</code> repo already cloned and a setup tutorial on the right.</p>
<p>Make sure you are logged in to the Google account that has your GCP project.</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Run the deployment script', 'content'=>'
<p>In the Cloud Shell terminal that opens, run:</p>
<pre>bash deploy/gcp/deploy.sh</pre>
<p>The script will ask you for:</p>
<ul>
<li><strong>Project ID</strong> — pre-filled from your current gcloud config</li>
<li><strong>Zone</strong> — e.g. <code>us-central1-a</code></li>
<li><strong>License key</strong>, <strong>domain name</strong>, <strong>SMTP credentials</strong>, <strong>OpenAI API key</strong></li>
</ul>
<p>After you confirm, it automatically reserves a static IP, creates firewall rules, launches the VM, and starts all containers.</p>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'Point your domain to the output IP', 'content'=>'
<p>The script prints the static external IP at the end. In your DNS provider, create an A record:</p>
<pre>talent.yourcompany.com  →  &lt;static IP from script output&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Access Nalam Pulse', 'content'=>'
<p>Visit <code>https://talent.yourcompany.com</code>. SSL is set up automatically by the installer via Let\'s Encrypt.</p>
<p>To check container status at any time, SSH into the VM and run:</p>
<pre>cd /opt/nalampulse && docker compose ps</pre>
'])
        </div>
    </div>
</div>

{{-- ── Azure Tab ─────────────────────────────────────────────────── --}}
<div id="pane-azure" class="deploy-pane" style="display:none">
    <div class="card" style="margin-top:0;border-top-left-radius:0;border-top-right-radius:0">
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:4px;color:var(--gray-800)">Deploy on Azure VM via ARM Template</h3>
            <p style="font-size:13px;color:var(--gray-500);margin-bottom:20px">The ARM template creates the VM, networking, and security group, then installs and starts all containers automatically. No SSH required.</p>

            @include('settings._deploy-step', ['n'=>1, 'title'=>'Click "Deploy to Azure" above', 'content'=>'
<p>The <strong>Deploy to Azure — ARM Template</strong> button opens the Azure Portal "Custom deployment" page with the Nalam Pulse template pre-loaded.</p>
<p>Sign in to your Azure account if prompted.</p>
'])

            @include('settings._deploy-step', ['n'=>2, 'title'=>'Fill in the Parameters form', 'content'=>'
<p>The deployment form asks for:</p>
<ul>
<li><strong>Subscription &amp; Resource Group</strong> — select existing or create new</li>
<li><strong>Admin Username / SSH Public Key</strong> — for optional SSH access later</li>
<li><strong>VM Size</strong> — defaults to <strong>Standard_B2s</strong> (2 vCPU, 4 GB). Use <strong>Standard_B2ms</strong> for production.</li>
<li><strong>License Key</strong>, <strong>Domain Name</strong>, <strong>SMTP credentials</strong>, <strong>OpenAI API key</strong></li>
</ul>
'])

            @include('settings._deploy-step', ['n'=>3, 'title'=>'Click "Review + create" → "Create"', 'content'=>'
<p>Azure validates the template, then provisions:</p>
<ul>
<li>Ubuntu 22.04 VM with a static Public IP</li>
<li>Network Security Group with ports 80, 443, 22 open</li>
<li>Docker, all Nalam Pulse containers, and Let\'s Encrypt SSL — all configured automatically</li>
</ul>
<p>Total time: <strong>~10 minutes</strong>. Watch progress in the deployment blade.</p>
'])

            @include('settings._deploy-step', ['n'=>4, 'title'=>'Point your domain to the output IP', 'content'=>'
<p>When deployment shows <strong>Your deployment is complete</strong>, click <strong>Outputs</strong> and copy the <strong>publicIP</strong> value.</p>
<pre>talent.yourcompany.com  →  &lt;publicIP from Outputs&gt;</pre>
'])

            @include('settings._deploy-step', ['n'=>5, 'title'=>'Access Nalam Pulse', 'content'=>'
<p>Visit <code>https://talent.yourcompany.com</code> once DNS propagates. You should see the Nalam Pulse login screen.</p>
<p>Default super admin credentials are in the VM boot log. <strong>Change your password immediately after first login.</strong></p>
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
