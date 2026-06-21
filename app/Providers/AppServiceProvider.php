<?php

namespace App\Providers;

use App\Support\Money;
use Illuminate\Support\Facades\Blade;
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
        // @money($paisa)  -> "₨ 1,234.00"   |  @moneyc($paisa) -> "₨ 12.3K" (compact)
        Blade::directive('money', fn ($expr) => "<?php echo \\App\\Support\\Money::format($expr); ?>");
        Blade::directive('moneyc', fn ($expr) => "<?php echo \\App\\Support\\Money::short($expr); ?>");
    }
}
