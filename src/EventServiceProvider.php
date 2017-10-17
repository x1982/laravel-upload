<?php
namespace Landers\LaravelUpload;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Landers\LaravelUpload\Events\Uploading::class => [
            //\Landers\LaravelUpload\Listeners\Uploading::class
        ],
        \Landers\LaravelUpload\Events\Uploaded::class => [
            \Landers\LaravelUpload\Listeners\Uploaded::class
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
