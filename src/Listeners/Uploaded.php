<?php

namespace Landers\LaravelUpload\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Uploaded implements ShouldQueue
{
    use \Landers\LaravelUpload\Traits\FileTypesTrait;

    /**
     * Create the event listener.
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  Uploaded $event
     * @return void
     */
    public function handle(\Landers\LaravelUpload\Events\Uploaded $event)
    {
        //dp($event);
        //$result = $event->result;
        //$file = $event->file;
        //
        //dp($file);
        //
        //// open an image file
        //$img = Image::make('public/foo.jpg');
        //
        //// resize image instance
        //$img->resize(320, 240);
        //
        //// insert a watermark
        //$img->insert('public/watermark.png');
        //
        //// save image in desired format
        //$img->save('public/bar.jpg');
    }
}
