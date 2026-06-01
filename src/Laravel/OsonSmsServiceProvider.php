<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TexHub\OsonSms\Clients\SmsClient;
use TexHub\OsonSms\Config;
use TexHub\OsonSms\OsonSms as OsonSmsClient;

class OsonSmsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/oson-sms.php', 'oson-sms');

        $this->app->singleton(Config::class, function ($app): Config {
            return Config::fromArray((array) $app['config']->get('oson-sms', []));
        });

        $this->app->singleton(OsonSmsClient::class, function ($app): OsonSmsClient {
            return new OsonSmsClient($app->make(Config::class));
        });

        $this->app->alias(OsonSmsClient::class, 'oson-sms');

        $this->app->singleton(SmsClient::class, function ($app): SmsClient {
            return $app->make(OsonSmsClient::class)->sms();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/oson-sms.php' => $this->app->configPath('oson-sms.php'),
            ], 'oson-sms-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            Config::class,
            OsonSmsClient::class,
            SmsClient::class,
            'oson-sms',
        ];
    }
}
