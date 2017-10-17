<?php

namespace Landers\LaravelUpload\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use \Landers\LaravelUpload\Traits\FileTypes;

class Uploaded implements ShouldQueue
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
    public function handle(\Landers\LaravelUpload\Events\Uploaded $event)
    {
        $result = $event->result;


    }
}
