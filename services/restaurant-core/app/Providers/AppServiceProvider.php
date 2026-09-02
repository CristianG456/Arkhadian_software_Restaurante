<?php

namespace App\Providers;

use App\Domain\Mobile\Services\MobileNotificationService;
use App\Domain\Orders\Events\OrderCancelled;
use App\Domain\Orders\Events\OrderConfirmed;
use App\Domain\Orders\Events\OrderItemCancelled;
use App\Domain\Orders\Events\TableSessionMoved;
use App\Domain\Orders\Events\TableWaiterTransferred;
use App\Domain\Production\Printing\PrintDocumentRenderer;
use App\Domain\Production\Printing\TestFilePrintDriver;
use App\Domain\Production\Printing\WindowsRawSharePrintDriver;
use App\Domain\Production\Services\PrintJobProcessor;
use App\Domain\Production\Services\ProductionRoutingService;
use App\Domain\System\Services\InstallationIdentityService;
use App\Domain\System\Services\InternetConnectivityService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InstallationIdentityService::class, fn () => new InstallationIdentityService(
            config('system.installation_id_path'),
        ));

        $this->app->singleton(InternetConnectivityService::class, fn () => new InternetConnectivityService(
            config('system.internet_check.targets'),
            config('system.internet_check.timeout'),
        ));
        $this->app->singleton(PrintJobProcessor::class, fn ($app) => new PrintJobProcessor(
            [$app->make(TestFilePrintDriver::class), $app->make(WindowsRawSharePrintDriver::class)],
            $app->make(PrintDocumentRenderer::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderConfirmed::class, fn (OrderConfirmed $event) => app(ProductionRoutingService::class)->routeOrder($event->orderId));
        Event::listen(OrderConfirmed::class, fn (OrderConfirmed $event) => app(MobileNotificationService::class)->orderConfirmed($event->orderId));
        Event::listen(TableWaiterTransferred::class, fn (TableWaiterTransferred $event) => app(MobileNotificationService::class)->transferred($event));
        Event::listen(OrderCancelled::class, fn (OrderCancelled $event) => app(ProductionRoutingService::class)->cancelOrder($event->orderId));
        Event::listen(OrderItemCancelled::class, fn (OrderItemCancelled $event) => app(ProductionRoutingService::class)->cancelItem($event->itemId));
        Event::listen(TableSessionMoved::class, fn (TableSessionMoved $event) => app(ProductionRoutingService::class)->tableMoved($event->sessionId, $event->fromTableId));
    }
}
