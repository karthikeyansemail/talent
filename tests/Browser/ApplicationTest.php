<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ApplicationTest extends DuskTestCase
{
    private function navigateToFirstApplication(Browser $browser): void
    {
        $this->loginAs($browser, 'hr@acme.com');
        $browser->visit('/jobs')
            ->clickLink('Senior Backend Developer')
            ->waitForText('Applications', 10);

        // Click the first "View" link in the applications table
        $browser->waitFor('.table-actions a', 10)
            ->pause(1000);

        // Use JS navigation for reliability (first test in suite may have stale click targets)
        $browser->script("document.querySelector('.table-actions a').click()");
        $browser->waitForText('Application Details', 15);
    }

    public function test_application_show_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->navigateToFirstApplication($browser);
            $browser->assertSee('Application Details')
                ->assertSee('Application Info');
        });
    }

    public function test_application_shows_candidate_info(): void
    {
        $this->browse(function (Browser $browser) {
            $this->navigateToFirstApplication($browser);
            $browser->assertSee('View Candidate')
                ->assertSee('View Job');
        });
    }

    public function test_can_update_application_stage(): void
    {
        $this->browse(function (Browser $browser) {
            $this->navigateToFirstApplication($browser);
            $browser->select('stage', 'hr_screening')
                ->press('Update')
                ->waitForText('updated', 10);
        });
    }

    public function test_feedback_modal_opens(): void
    {
        $this->browse(function (Browser $browser) {
            $this->navigateToFirstApplication($browser);
            $browser->assertSee('Interview Feedback')
                ->press('Add Feedback')
                ->waitFor('#feedbackModal.active')
                ->pause(500)
                ->waitForText('Submit Feedback', 5)
                ->assertPresent('select[name="recommendation"]');
        });
    }

    public function test_can_submit_feedback(): void
    {
        $this->browse(function (Browser $browser) {
            $this->navigateToFirstApplication($browser);
            $browser->press('Add Feedback')
                ->waitFor('#feedbackModal.active');

            // Fill in all fields within the modal scope
            $browser->within('#feedbackModal', function ($modal) {
                $modal->select('stage', 'hr_screening')
                    ->click('#feedbackStarPicker button[data-value="4"]')
                    ->pause(300);

                // Use JS to set recommendation (Dusk select() unreliable inside modal scope)
                $modal->script("document.querySelector('#feedbackModal select[name=\"recommendation\"]').value = 'yes'");
                $modal->script("document.querySelector('#feedbackModal select[name=\"recommendation\"]').dispatchEvent(new Event('change'))");

                $modal->type('strengths', 'Strong technical skills')
                    ->type('notes', 'Dusk automated test feedback')
                    ->press('Submit Feedback');
            });

            $browser->waitForText('Dusk automated test feedback', 10);
        });
    }

    public function test_ai_analysis_button_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->navigateToFirstApplication($browser);
            $browser->assertSee('Run AI Analysis');
        });
    }
}
