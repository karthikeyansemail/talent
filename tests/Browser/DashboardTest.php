<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    public function test_dashboard_loads_for_admin(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->assertPathIs($this->path('dashboard'))
                ->assertSee('Dashboard')
                ->assertSee('Total Jobs')
                ->assertSee('Total Candidates')
                ->assertSee('Applications')
                ->assertSee('Employees')
                ->assertSee('Projects');
        });
    }

    public function test_dashboard_shows_stat_cards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->assertPresent('.stat-card')
                ->assertSee('Hiring Pipeline');
        });
    }

    public function test_dashboard_shows_recent_applications(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->assertSee('Recent Applications');
        });
    }

    public function test_sidebar_navigation_links_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'admin@acme.com');
            $browser->assertSeeLink('Dashboard')
                ->assertSeeLink('Candidates')
                ->assertSeeLink('Employees')
                ->assertSeeLink('Projects');
        });
    }
}
