<?php
// app/Providers/RepositoryServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\ClientRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\ClientRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
    }

    public function boot()
    {
        //
    }
}
