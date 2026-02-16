<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthTest extends DuskTestCase
{
    public function test_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->logout($browser);
            $browser->visit('/login')
                ->assertSee('Welcome back')
                ->assertSee('Sign in')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]');
        });
    }

    public function test_admin_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->assertPathIs($this->path('dashboard'))
                ->assertSee('Dashboard');
        });
    }

    public function test_hr_manager_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->assertPathIs($this->path('dashboard'))
                ->assertSee('Dashboard');
        });
    }

    public function test_resource_manager_can_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->assertPathIs($this->path('dashboard'))
                ->assertSee('Dashboard');
        });
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->browse(function (Browser $browser) {
            $this->logout($browser);
            $browser->visit('/login')
                ->waitFor('input[name="email"]', 10)
                ->pause(500)
                ->type('email', 'admin@acme.com')
                ->type('password', 'wrongpassword')
                ->press('Sign in')
                ->waitForText('Invalid credentials', 10);
        });
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->browse(function (Browser $browser) {
            $this->logout($browser);
            $browser->visit('/login')
                ->waitFor('input[name="email"]', 10)
                ->pause(500)
                ->type('email', 'nobody@nowhere.com')
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForText('Invalid credentials', 10);
        });
    }

    public function test_user_can_logout(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->assertPathIs($this->path('dashboard'));

            // Submit the logout form directly via JS (button is an SVG icon)
            $browser->script("document.querySelector('form[action*=\"logout\"]').submit()");
            $browser->pause(2000);
            $browser->assertPathIs($this->path('login'));
        });
    }

    public function test_register_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->logout($browser);
            $browser->visit('/register')
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('input[name="org_name"]');
        });
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->logout($browser);
            $browser->visit('/dashboard')
                ->waitForLocation($this->path('login'), 10)
                ->assertPathIs($this->path('login'));
        });
    }
}
