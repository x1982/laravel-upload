<?php
namespace Landers\LaravelUpload;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

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

        // $this->replaceStorageManager();

        Route::any('/ueditor', 'Landers\LaravelUpload\UEditorController@upload');
    }

    private function replaceStorageManager()
    {
        //$this->app->singleton(
        //    \Overtrue\LaravelUEditor\StorageManager::class,
        //    \Landers\LaravelUpload\StorageManager::class
        //);
    }
}
