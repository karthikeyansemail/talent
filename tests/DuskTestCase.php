<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Base path prefix for all routes (XAMPP subfolder).
     */
    protected string $base = '/talent/public';

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Helper: build full path with base prefix.
     */
    protected function path(string $uri): string
    {
        return $this->base . '/' . ltrim($uri, '/');
    }

    /**
     * Helper: ensure the browser is logged out.
     */
    protected function logout($browser)
    {
        // Delete ALL cookies then navigate away so the browser drops all session state
        $browser->driver->manage()->deleteAllCookies();
        $browser->driver->navigate()->to('about:blank');
        $browser->pause(300);
        return $browser;
    }

    /**
     * Helper: login as a specific user by email.
     */
    protected function loginAs($browser, string $email = 'admin@acme.com', string $password = 'password')
    {
        $this->logout($browser);

        $browser->visit('/login')
            ->waitFor('input[name="email"]', 10)
            ->pause(500)
            ->type('email', $email)
            ->type('password', $password)
            ->press('Sign in')
            ->waitForLocation($this->path('dashboard'), 15);

        return $browser;
    }
}
