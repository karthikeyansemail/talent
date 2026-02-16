<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CandidateTest extends DuskTestCase
{
    public function test_candidates_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates')
                ->assertSee('Candidates')
                ->assertSeeLink('New Candidate')
                ->assertSee('John Smith')
                ->assertSee('Emily Chen')
                ->assertSee('Priya Patel');
        });
    }

    public function test_candidates_can_be_searched(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates')
                ->type('search', 'John')
                ->press('Search')
                ->assertSee('John Smith')
                ->assertDontSee('Emily Chen');
        });
    }

    public function test_create_candidate_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates/create')
                ->assertSee('Add Candidate')
                ->assertPresent('input[name="first_name"]')
                ->assertPresent('input[name="last_name"]')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="phone"]')
                ->assertPresent('select[name="source"]');
        });
    }

    public function test_can_create_candidate_manually(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates/create')
                ->type('first_name', 'Dusk')
                ->type('last_name', 'Tester')
                ->type('email', 'dusk.tester@example.com')
                ->type('phone', '555-9999')
                ->type('current_company', 'Test Corp')
                ->type('current_title', 'QA Lead')
                ->type('experience_years', '5')
                ->select('source', 'direct')
                ->type('notes', 'Created by Dusk automated testing')
                ->press('Create Candidate')
                ->waitForText('Dusk Tester', 10)
                ->assertSee('Dusk Tester');
        });
    }

    public function test_can_view_candidate_details(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates')
                ->clickLink('John Smith')
                ->assertSee('John Smith')
                ->assertSee('Google')
                ->assertSee('Senior Developer');
        });
    }

    public function test_can_edit_candidate(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates')
                ->clickLink('Emily Chen')
                ->clickLink('Edit')
                ->assertSee('Edit Candidate')
                ->assertInputValue('first_name', 'Emily')
                ->clear('phone')
                ->type('phone', '555-9999')
                ->press('Update Candidate')
                ->waitForText('Emily Chen', 10)
                ->assertSee('555-9999');
        });
    }

    public function test_candidate_show_displays_resumes(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates')
                ->clickLink('John Smith')
                ->assertSee('Resumes');
        });
    }

    public function test_candidate_show_displays_applications(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates')
                ->clickLink('John Smith')
                ->assertSee('Applications')
                ->assertSee('Senior Backend Developer');
        });
    }

    public function test_create_candidate_requires_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/candidates/create')
                ->press('Create Candidate')
                // HTML5 required validation should prevent submit
                ->assertPathIs($this->path('candidates/create'));
        });
    }
}
