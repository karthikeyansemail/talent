<?php

namespace App\Providers;

use App\Services\BrandingService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.*', function ($view) {
            $org = Auth::check() ? Auth::user()->currentOrganization() : null;
            $view->with('branding', BrandingService::resolve($org));
            $view->with('themeCss', ThemeService::cssOverrides($org));
        });
    }
}
