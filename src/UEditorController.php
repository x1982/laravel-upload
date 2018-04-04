<?php

namespace Landers\LaravelUpload;

use Illuminate\Http\Request;
use Overtrue\LaravelUEditor\UEditorController as LaravelUEditorController;
use Illuminate\Support\Facades\Storage;

/**
 * Class UEditorController.
 */
class UEditorController extends LaravelUEditorController
{
    public function upload(Request $request)
    {
        $upload = config('ueditor.upload');

        switch ($request->get('action')) {
            case 'config':
            case $upload['imageManagerActionName']:
            case $upload['fileManagerActionName']:
                return $this->serve($request);

            default:
                /**
                 * @var StorageManager $storage
                 */
                $storage_disk = myams_config('upload.disk', 'public');
                $disk = Storage::disk($storage_disk);
                $storage = new StorageManager($disk);
                return $storage->upload($request);
        }
    }
}
