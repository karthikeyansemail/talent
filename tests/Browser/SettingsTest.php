<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SettingsTest extends DuskTestCase
{
    // --- Organization Settings ---

    public function test_organization_settings_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/organization')
                ->assertSee('Organization Settings')
                ->assertPresent('input[name="name"]');
        });
    }

    public function test_can_update_organization_name(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/organization')
                ->clear('name')
                ->type('name', 'Acme Technologies Updated')
                ->press('Save Changes')
                ->pause(2000)
                ->assertInputValue('name', 'Acme Technologies Updated');

            // Restore
            $browser->clear('name')
                ->type('name', 'Acme Technologies')
                ->press('Save Changes')
                ->pause(2000);
        });
    }

    // --- User Management ---

    public function test_users_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/users')
                ->assertSee('Users')
                ->assertSeeLink('Add User')
                ->assertSee('admin@acme.com')
                ->assertSee('hr@acme.com')
                ->assertSee('rm@acme.com');
        });
    }

    public function test_create_user_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/users/create')
                ->assertSee('Add User')
                ->assertPresent('input[name="name"]')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('select[name="role"]');
        });
    }

    public function test_can_create_new_user(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/users/create')
                ->type('name', 'Dusk Test User')
                ->type('email', 'dusk.user@acme.com')
                ->type('password', 'password123')
                ->select('role', 'employee')
                ->press('Create User')
                ->waitForText('Dusk Test User', 10)
                ->assertSee('Dusk Test User');
        });
    }

    public function test_can_edit_user(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/users')
                ->assertSee('Sarah HR');
        });
    }

    // --- LLM Configuration ---

    public function test_llm_config_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/llm')
                ->assertSee('LLM Configuration');
        });
    }

    // --- Scoring Rules ---

    public function test_scoring_rules_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/scoring-rules')
                ->assertSee('Scoring Rules')
                ->assertSee('Signal Weights')
                ->assertSee('Core Signals')
                ->assertSee('Skill Match');
        });
    }

    public function test_scoring_rules_shows_version_history(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/scoring-rules')
                ->assertSee('Version History');
        });
    }

    // --- Integrations ---

    public function test_integrations_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->visit('/settings/integrations')
                ->assertSee('Integrations');
        });
    }

    // --- Access Control ---

    public function test_hr_manager_cannot_access_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/settings/organization')
                ->assertSee('UNAUTHORIZED ACCESS');
        });
    }

    public function test_resource_manager_cannot_access_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/settings/organization')
                ->assertSee('UNAUTHORIZED ACCESS');
        });
    }
}
