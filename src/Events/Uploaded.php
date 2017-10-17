<?php

/*
 * This file is part of the overtrue/laravel-ueditor.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Landers\LaravelUpload\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class Uploading.
 *
 * @author overtrue <i@overtrue.me>
 */
class Uploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public $result;

    /**
     * Uploaded constructor.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param array                                               $result
     */
    public function __construct( array $result)
    {
        $this->result = $result;
    }
}
