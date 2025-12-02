<?php
// app/Providers/RepositoryServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\ClientRepositoryInterface;
use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Repositories\Admin\InvoiceSummary\InvoiceSummaryInterface;
use App\Repositories\ClientRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\UserRepository;
use App\Repositories\Admin\InvoiceSummary\InvoiceSummaryRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // إضافة الربط للـ InvoiceSummary
        $this->app->bind(InvoiceSummaryInterface::class, InvoiceSummaryRepository::class);
    }
}
