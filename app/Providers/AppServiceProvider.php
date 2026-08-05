<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
        Carbon::setLocale(config('app.locale'));
        \Livewire\Livewire::component('chatbot-widget', \App\Livewire\ChatbotWidget::class);

        Mail::extend('brevo', function (array $config = []) {
            $factory = new BrevoTransportFactory(null, HttpClient::create());

            return $factory->create(new Dsn(
                'brevo+api',
                'default',
                $config['key'] ?? config('services.brevo.key'),
            ));
        });
    }
}
