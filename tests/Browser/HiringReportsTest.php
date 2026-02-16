<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class HiringReportsTest extends DuskTestCase
{
    public function test_hiring_reports_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/hiring/reports')
                ->assertSee('Hiring Reports');
        });
    }
}
