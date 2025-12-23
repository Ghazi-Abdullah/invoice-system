<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repository\Admin\Invoice\InvoiceInterface;
use App\Repository\Admin\Invoice\InvoiceRepository;
use App\Repository\Admin\Client\ClientInterface;
use App\Repository\Admin\Client\ClientRepository;
use App\Repository\Admin\User\UserInterface;
use App\Repository\Admin\User\UserRepository;
use App\Repository\Admin\AdminGroup\AdminGroupInterface;
use App\Repository\Admin\AdminGroup\AdminGroupRepository;
use App\Repository\Admin\Report\ReportInterface;
use App\Repository\Admin\Report\ReportRepository;
use App\Repository\Admin\Permission\PermissionInterface;
use App\Repository\Admin\Permission\PermissionRepository;
use App\Services\InvoiceService;
use App\Services\ReportService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
         $this->app->singleton(InvoiceService::class, function ($app) {
            return new InvoiceService();
        });

        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService();
        });
        // Bind Repositories
        $this->app->bind(InvoiceInterface::class, InvoiceRepository::class);
        $this->app->bind(ClientInterface::class, ClientRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(AdminGroupInterface::class, AdminGroupRepository::class);
        $this->app->bind(ReportInterface::class, ReportRepository::class);
        $this->app->bind(PermissionInterface::class, PermissionRepository::class);
    }

     public function boot()
    {
        // Register validation rules
        \Validator::extend('valid_currency', function ($attribute, $value, $parameters, $validator) {
            $validCurrencies = [
                \App\Constants\Constants::CURRENCY_USD,
                \App\Constants\Constants::CURRENCY_EUR,
                \App\Constants\Constants::CURRENCY_GBP,
                \App\Constants\Constants::CURRENCY_SAR,
                \App\Constants\Constants::CURRENCY_AED
            ];

            return in_array($value, $validCurrencies);
        });

        \Validator::replacer('valid_currency', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'The selected currency is not valid.');
        });

        // Set global pagination limit
        \Illuminate\Pagination\Paginator::useBootstrap();
    }
}
