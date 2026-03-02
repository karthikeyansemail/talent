<?php
/**
 * Nalam Pulse — Portal Config
 * portal.nalampulse.com/includes/config.php
 *
 * FILL IN all values before deployment.
 */

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'nalampulse_portal');
define('DB_USER', 'root');
define('DB_PASS', '');          // Set in production

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_NAME',   'np_portal');
define('SESSION_SECURE', false);    // set true when HTTPS is live
define('SESSION_DOMAIN', 'portal.nalampulse.com');

// ── Telegram (chat widget notifications) ─────────────────────────────────────
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID',   'YOUR_CHAT_ID_HERE');

// ── Stripe (USD) ──────────────────────────────────────────────────────────────
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_YOUR_KEY_HERE');
define('STRIPE_SECRET_KEY',      'sk_live_YOUR_KEY_HERE');
define('STRIPE_WEBHOOK_SECRET',  'whsec_YOUR_SECRET_HERE');

// Stripe Price IDs (create these in Stripe Dashboard)
define('STRIPE_PRICE_CLOUD_MONTHLY', 'price_XXXX');   // $49/mo
define('STRIPE_PRICE_CLOUD_ANNUAL',  'price_XXXX');   // $490/yr
define('STRIPE_PRICE_SELF_HOSTED',   'price_XXXX');   // $999 one-time

// ── Razorpay (INR) ────────────────────────────────────────────────────────────
define('RAZORPAY_KEY_ID',     'rzp_live_YOUR_KEY_ID_HERE');
define('RAZORPAY_KEY_SECRET', 'YOUR_RAZORPAY_SECRET_HERE');

// ── App settings ──────────────────────────────────────────────────────────────
define('APP_NAME',    'Nalam Pulse Portal');
define('APP_URL',     'https://portal.nalampulse.com');
define('MAIN_URL',    'https://nalampulse.com');
define('APP_URL_',    'https://app.nalampulse.com');
define('SUPPORT_EMAIL', 'support@nalampulse.com');
define('FROM_EMAIL',    'noreply@nalampulse.com');

// ── Base path ─────────────────────────────────────────────────────────────────
// Local XAMPP: '/talent/portal'   (files at localhost/talent/portal/)
// Production:  ''                 (files at portal.nalampulse.com/)
define('BASE', '/talent/portal');

// ── Environment ───────────────────────────────────────────────────────────────
define('APP_ENV', 'local');        // 'local' | 'production'
define('APP_DEBUG', true);
