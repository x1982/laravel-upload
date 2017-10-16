<?php
namespace Landers\LaravelUpload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Overtrue\LaravelUEditor\Events\Uploaded;
use Overtrue\LaravelUEditor\Events\Uploading;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class StorageManager.
 */
class StorageManager
{
    use UrlResolverTrait;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $disk;

    /**
     * Constructor.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     */
    public function __construct(Filesystem $disk)
    {
        $this->disk = $disk;
    }

    /**
     * Upload a file.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $config = $this->getUploadConfig($request->get('key'));

        if (!$request->hasFile($config['field_name'])) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }

        $file = $request->file($config['field_name']);

        if ($error = $this->fileHasError($file, $config)) {
            return $this->error($error);
        }

        $filename = $this->getFilename($file, $config);

        if ($this->eventSupport()) {
            $modifiedFilename = event(new Uploading($file, $filename, $config), [], true);
            $filename = !is_null($modifiedFilename) ? $modifiedFilename : $filename;
        }

        $this->store($file, $filename);

        $response = [
            'urls' => [
                'original' => $this->getUrl($filename),
                'small' => $this->getUrl($filename),
                'mini' => $this->getUrl($filename),
            ],
            'size' => $file->getSize(),
        ];

        if ($this->eventSupport()) {
            event(new Uploaded($file, $response));
        }

        return build_api_data( $response );
    }

    /**
     * @return bool
     */
    public function eventSupport()
    {
        return trait_exists('Illuminate\Foundation\Events\Dispatchable');
    }

    /**
     * Store file.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param string                                              $filename
     *
     * @return mixed
     */
    protected function store(UploadedFile $file, $filename)
    {
        return $this->disk->put($filename, fopen($file->getRealPath(), 'r+'));
    }

    /**
     * Validate the input file.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param array                                               $config
     *
     * @return bool|string
     */
    protected function fileHasError(UploadedFile $file, array $config)
    {
        $error = false;

        if (!$file->isValid()) {
            $error = $file->getError();
        } elseif ( $max_size = array_get($config, 'max_size', 0)) {
            if ( $file->getSize() > $max_size * 1024 ) {
                $error = 'upload.ERROR_SIZE_EXCEED';
            }
        } elseif ($allow_types = array_get($config, 'allow_types')) {
            $allow_types = explode(',', $allow_types);
            $extension = $file->getClientOriginalExtension();
            if ( !in_array( $extension, $allow_types )) {
                $error = 'upload.ERROR_TYPE_NOT_ALLOWED';
            }
        }

        return $error;
    }

    /**
     * Get the new filename of file.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param array $config
     *
     * @return string
     */
    protected function getFilename(UploadedFile $file, array $config)
    {
        $ext = '.'.$file->getClientOriginalExtension();

        if (  array_get($config, 'is_original_filename') ) {
            $original_filename = \Illuminate\Support\Facades\Request::input('filename');
        } else {
            $original_filename = '';
        }

        $save_path = array_get( $config, 'save_path');

        $filename = !$original_filename ? md5($file->getFilename()).$ext : $file->getClientOriginalName();

        $path_format = array_get( $config, 'path_format');
        $file_path = $save_path . $this->formatPath($path_format, $filename);

        return $file_path;
    }

    /**
     * Get configuration of current action.
     *
     * @param string $action
     *
     * @return array
     */
    protected function getUploadConfig( string $key)
    {
        $config = app(UploadConfigModel::class)->findOrFail($key)->toArray();
        $config['field_name'] = 'file';
        return $config;
    }

    /**
     * Make error response.
     *
     * @param $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($message)
    {
        return response()->json(['state' => trans("ueditor::upload.{$message}")]);
    }

    /**
     * Format the storage path.
     *
     * @param string $path
     * @param string $filename
     *
     * @return mixed
     */
    protected function formatPath($path, $filename)
    {
        $replacement = array_merge(explode('-', date('Y-y-m-d-H-i-s')), [$filename, time()]);
        $placeholders = ['{yyyy}', '{yy}', '{mm}', '{dd}', '{hh}', '{ii}', '{ss}', '{filename}', '{time}'];
        $path = str_replace($placeholders, $replacement, $path);

        //替换随机字符串
        if (preg_match('/\{rand\:([\d]*)\}/i', $path, $matches)) {
            $length = min($matches[1], strlen(PHP_INT_MAX));
            $path = preg_replace('/\{rand\:[\d]*\}/i', str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT), $path);
        }

        if (!str_contains($path, $filename)) {
            $path = str_finish($path, '/').$filename;
        }

        return $path;
    }
}