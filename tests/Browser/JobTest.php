<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class JobTest extends DuskTestCase
{
    public function test_jobs_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs')
                ->assertSee('Job Postings')
                ->assertSeeLink('New Job')
                ->assertSee('Senior Backend Developer')
                ->assertSee('React Frontend Developer')
                ->assertSee('ML Engineer');
        });
    }

    public function test_jobs_can_be_filtered_by_status(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs')
                ->select('status', 'open')
                // Jobs filter auto-submits on change
                ->pause(2000)
                ->assertSee('Senior Backend Developer');
        });
    }

    public function test_create_job_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs/create')
                ->assertSee('Create Job Posting')
                ->assertPresent('input[name="title"]')
                ->assertPresent('select[name="department_id"]')
                ->assertPresent('textarea[name="description"]');
        });
    }

    public function test_can_create_a_new_job(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs/create')
                ->type('title', 'Dusk Test - QA Engineer')
                ->type('description', 'This is a test job created by Dusk automated testing.')
                ->type('requirements', 'Testing experience, Selenium, PHP')
                ->type('min_experience', '2')
                ->type('max_experience', '5')
                ->select('employment_type', 'full_time')
                ->type('location', 'Remote')
                ->select('status', 'draft')
                ->press('Create Job')
                ->waitForText('Dusk Test - QA Engineer', 10)
                ->assertSee('Dusk Test - QA Engineer');
        });
    }

    public function test_can_view_job_details(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs')
                ->clickLink('Senior Backend Developer')
                ->assertSee('Senior Backend Developer')
                ->assertSee('Description')
                ->assertSee('Applications');
        });
    }

    public function test_can_edit_a_job(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs')
                ->clickLink('Senior Backend Developer')
                ->clickLink('Edit Job')
                ->waitFor('input[name="title"]', 10)
                ->assertSee('Edit Job')
                ->assertInputValue('title', 'Senior Backend Developer')
                ->clear('title')
                ->type('title', 'Senior Backend Developer (Updated)')
                ->press('Update Job')
                ->waitForText('Senior Backend Developer (Updated)', 10)
                ->assertSee('Senior Backend Developer (Updated)');

            // Restore original title
            $browser->clickLink('Edit Job')
                ->clear('title')
                ->type('title', 'Senior Backend Developer')
                ->press('Update Job')
                ->waitForText('Senior Backend Developer', 10);
        });
    }

    public function test_job_show_displays_applications(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/jobs')
                ->clickLink('Senior Backend Developer')
                ->assertSee('Applications')
                ->assertSee('John Smith');
        });
    }

    public function test_resource_manager_cannot_access_jobs(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/jobs')
                ->assertDontSee('Job Postings');
        });
    }
}
