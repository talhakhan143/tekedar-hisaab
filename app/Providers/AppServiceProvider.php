<?php

namespace App\Providers;

use App\Support\Money;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
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
        // APP_URL https ho to saare bane hue link bhi https hon. .htaccess ka
        // redirect pehli layer hai, ye doosri: agar kabhi wo rule ur jaye to
        // form ka action http na ban jaye, warna secure session cookie nahi
        // jati aur login par 419 Page Expired aata hai.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // @money($paisa)  -> "₨ 1,234.00"   |  @moneyc($paisa) -> "₨ 12.3K" (compact)
        Blade::directive('money', fn ($expr) => "<?php echo \\App\\Support\\Money::format($expr); ?>");
        Blade::directive('moneyc', fn ($expr) => "<?php echo \\App\\Support\\Money::short($expr); ?>");
    }
}
