<?php
namespace Landers\LaravelUpload;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class UploadServiceProvider extends ServiceProvider
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

        Route::any('/ueditor', 'Landers\LaravelUpload\UEditorController@upload');

        parent::boot();
    }
}
