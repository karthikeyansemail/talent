<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProjectTest extends DuskTestCase
{
    public function test_projects_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects')
                ->assertSee('Projects')
                ->assertSeeLink('New Project')
                ->assertSee('Customer Portal Redesign')
                ->assertSee('ML Recommendation Engine');
        });
    }

    public function test_projects_can_be_filtered_by_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects')
                ->select('status', 'planning')
                // Projects filter auto-submits on change
                ->pause(2000)
                ->assertSee('Customer Portal Redesign');
        });
    }

    public function test_create_project_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects/create')
                ->assertSee('Create Project')
                ->assertPresent('input[name="name"]')
                ->assertPresent('textarea[name="description"]')
                ->assertPresent('select[name="complexity_level"]')
                ->assertPresent('select[name="status"]')
                ->assertPresent('input[name="start_date"]')
                ->assertPresent('input[name="end_date"]');
        });
    }

    public function test_can_create_project(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects/create')
                ->type('name', 'Dusk Test Project')
                ->type('description', 'A project created by automated Dusk testing')
                ->select('complexity_level', 'medium')
                ->select('status', 'planning')
                ->type('domain_context', 'Internal testing framework');

            // Set date inputs via JS (type() doesn't work reliably for HTML date inputs in Chrome)
            $browser->script("document.querySelector('input[name=\"start_date\"]').value = '2026-03-01'");
            $browser->script("document.querySelector('input[name=\"end_date\"]').value = '2026-06-30'");

            $browser->press('Create Project')
                ->waitForText('Dusk Test Project', 15)
                ->assertSee('Dusk Test Project');
        });
    }

    public function test_can_view_project_details(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects')
                ->clickLink('Customer Portal Redesign')
                ->assertSee('Customer Portal Redesign')
                ->assertSee('Project Details')
                ->assertSee('Requirements');
        });
    }

    public function test_can_edit_project(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects')
                ->clickLink('Customer Portal Redesign')
                ->clickLink('Edit Project')
                ->waitFor('input[name="name"]', 10)
                ->assertSee('Edit Project')
                ->assertInputValue('name', 'Customer Portal Redesign');

            // Use JS to set complexity (Dusk select() sometimes unreliable)
            $browser->script("document.querySelector('select[name=\"complexity_level\"]').value = 'critical'");
            $browser->press('Update Project')
                ->waitForText('updated', 10);

            // Verify complexity changed — the stage-badge shows ucfirst
            $browser->assertSee('Critical');

            // Restore
            $browser->clickLink('Edit Project')
                ->waitFor('input[name="name"]', 10);
            $browser->script("document.querySelector('select[name=\"complexity_level\"]').value = 'high'");
            $browser->press('Update Project')
                ->waitForText('updated', 10);
        });
    }

    public function test_project_show_has_find_resources_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/projects')
                ->clickLink('Customer Portal Redesign')
                ->assertSee('Find Best Resources');
        });
    }
}
