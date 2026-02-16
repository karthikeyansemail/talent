<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EmployeeTest extends DuskTestCase
{
    public function test_employees_index_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees')
                ->assertSee('Employees')
                ->assertSeeLink('New Employee')
                ->assertSee('Alice Wang')
                ->assertSee('Bob Martinez')
                ->assertSee('Carol Davis');
        });
    }

    public function test_employees_can_be_searched(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees')
                ->type('search', 'Alice')
                ->press('Filter')
                ->assertSee('Alice Wang')
                ->assertDontSee('Bob Martinez');
        });
    }

    public function test_employees_can_be_filtered_by_department(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees')
                ->assertPresent('select[name="department_id"]');
        });
    }

    public function test_create_employee_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees/create')
                ->assertSee('Add Employee')
                ->assertPresent('input[name="first_name"]')
                ->assertPresent('input[name="last_name"]')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="designation"]')
                ->assertPresent('select[name="department_id"]');
        });
    }

    public function test_can_create_employee(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees/create')
                ->type('first_name', 'Dusk')
                ->type('last_name', 'Employee')
                ->type('email', 'dusk.employee@acme.com')
                ->type('designation', 'Test Engineer')
                ->press('Create Employee')
                ->waitForText('Dusk Employee', 10)
                ->assertSee('Dusk Employee');
        });
    }

    public function test_can_view_employee_details(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees')
                ->clickLink('Alice Wang')
                ->waitForText('Employee Details', 10)
                ->assertSee('Alice Wang')
                ->assertSee('Engineering');
        });
    }

    public function test_can_edit_employee(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees')
                ->clickLink('Alice Wang')
                ->waitForText('Employee Details', 10)
                ->clickLink('Edit')
                ->assertSee('Edit Employee')
                ->clear('designation')
                ->type('designation', 'Staff Engineer')
                ->press('Update Employee')
                ->waitForText('Staff Engineer', 10)
                ->assertSee('Staff Engineer');

            // Restore
            $browser->clickLink('Edit')
                ->clear('designation')
                ->type('designation', 'Senior Engineer')
                ->press('Update Employee')
                ->waitForText('Senior Engineer', 10);
        });
    }

    public function test_import_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'rm@acme.com');
            $browser->visit('/employees/import')
                ->assertSee('Import Employees');
        });
    }

    public function test_hr_manager_cannot_access_employees(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAs($browser, 'hr@acme.com');
            $browser->visit('/employees')
                ->assertDontSee('New Employee');
        });
    }
}
