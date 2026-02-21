<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect(string $provider)
    {
        $setting = SsoSetting::where('provider', $provider)->where('is_enabled', true)->first();

        if (!$setting) {
            return redirect()->route('login')->withErrors(['email' => 'That SSO provider is not enabled.']);
        }

        $this->configureSocialite($provider, $setting);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        $setting = SsoSetting::where('provider', $provider)->where('is_enabled', true)->first();

        if (!$setting) {
            return redirect()->route('login')->withErrors(['email' => 'That SSO provider is not enabled.']);
        }

        $this->configureSocialite($provider, $setting);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'SSO authentication failed. Please try again.']);
        }

        $email = $socialUser->getEmail();

        if (!$email) {
            return redirect()->route('login')->withErrors(['email' => 'No email address was returned by the SSO provider.']);
        }

        $user = User::where('email', $email)->where('is_active', true)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'email' => "No account found for {$email}. Contact your organization administrator to create one for you.",
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }

    private function configureSocialite(string $provider, SsoSetting $setting): void
    {
        $redirectUrl = url("/auth/{$provider}/callback");
        $extra       = $setting->extra_config ?? [];

        $config = [
            'client_id'     => $setting->client_id ?? '',
            'client_secret' => $setting->client_secret ?? '',
            'redirect'      => $redirectUrl,
        ];

        if ($provider === 'microsoft') {
            $config['tenant'] = $extra['tenant_id'] ?? 'common';
        }

        if ($provider === 'okta') {
            $config['base_url'] = isset($extra['okta_domain'])
                ? 'https://' . ltrim($extra['okta_domain'], 'https://')
                : '';
        }

        config(["services.{$provider}" => $config]);
    }
}
