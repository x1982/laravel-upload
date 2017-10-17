<?php

namespace Landers\LaravelUpload\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Uploading implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  OrderReceived $event
     * @return void
     */
    public function handle(\Landers\LaravelUpload\Events\Uploading $event)
    {
        dp($event);
    }
}
