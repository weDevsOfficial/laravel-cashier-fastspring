<?php
/**
 * This file implements Laravel Cashier Service Provider.
 *
 * @author    Justin Hartman <justin@22digital.co.za>
 * @copyright 2019 22 Digital
 * @since     v0.1
 */

namespace TwentyTwoDigital\CashierFastspring;

use Illuminate\Support\ServiceProvider;

/**
 * This class describes the Laravel Cashier Service Provider.
 *
 * {@inheritdoc}
 */
class CashierServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $time = time();

        // publish migrations
        $this->publishes([
            __DIR__ . '/../resources/migrations/create_subscriptions_table_for_cashier_fastspring.php' => sprintf(
                database_path('migrations') . '/%s_create_subscriptions_table_for_cashier_fastspring.php',
                date('Y_m_d_His', $time)
            ),
            __DIR__ . '/../resources/migrations/upgrade_user_table_for_cashier_fastspring.php' => sprintf(
                database_path('migrations') . '/%s_upgrade_user_table_for_cashier_fastspring.php',
                date('Y_m_d_His', ++$time)
            ),
            __DIR__ . '/../resources/migrations/create_invoices_table_for_cashier_fastspring.php' => sprintf(
                database_path('migrations') . '/%s_create_invoices_table_for_cashier_fastspring.php',
                date('Y_m_d_His', ++$time)
            ),
            __DIR__ . '/../resources/migrations/create_subscription_periods_table_for_cashier_fastspring.php' => sprintf(
                database_path('migrations') . '/%s_create_subscription_periods_table_for_cashier_fastspring.php',
                date('Y_m_d_His', ++$time)
            ),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
