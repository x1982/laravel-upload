<?php
namespace Landers\LaravelUpload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Landers\LaravelUpload\Events\Uploaded;
use Landers\LaravelUpload\Events\Uploading;
use Illuminate\Http\UploadedFile;
//use Intervention\Image\Facades\Image;

/**
 * Class StorageManager.
 */
class StorageManager
{
    use \Landers\LaravelUpload\Traits\UrlResolverTrait;
    use \Landers\LaravelUpload\Traits\ImageHandlerTrait;
    use \Landers\LaravelUpload\Traits\FileTypesTrait;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $disk;


    /**
     * @var Model $config
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     */
    public function __construct(Filesystem $disk)
    {
        $this->disk = $disk;

        $config = config('myams.upload.config');
        $this->config = app($config);
    }

    /**
     * Upload a file.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|string
     */
    public function upload(Request $request)
    {
        $key = $request->get('key', '1e617715a68693450baa01be80695e34');
        $config = $this->getUploadConfig($key);

        $field_name = null;
        foreach ($config['field_names'] as $item) {
            if ($request->hasFile($item)) {
                $field_name = $item;
                break;
            }
        }
        if (!$field_name) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }

        $file = $request->file($field_name);

        if ($error = $this->fileHasError($file, $config)) {
            return $this->error($error);
        }

        // 尚未处理好 与is_original_filename相关的逻辑，暂时先全部用false
        // $is_original_filename = array_get($config, 'is_original_filename');
        $is_original_filename = false;
        $filename = $this->getFilename($file, $config, $is_original_filename);

        if ($this->eventSupport()) {
            $modifiedFilename = event(new Uploading($file, $filename, $config), [], true);
            $filename = !is_null($modifiedFilename) ? $modifiedFilename : $filename;
        }

        //$this->store($file, $filename);

        $result = [
            'url' => $this->getUrl($filename),
            'originalName' => $file->getClientOriginalName(),
            'filename' => $filename,
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
            'type' => $file->getMimeType(),

            // ueditor
            'original' => $file->getClientOriginalName(),
            'state' => 'SUCCESS',
            'title' => $filename,
        ];
        $this->storeWithIfHandleImage( $file, $config, $result );

        if ($this->eventSupport()) {
            event( new Uploaded($file, $result) );
        }

        unset( $result['filename'] );
        return $result;
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
     * @param UploadedFile $file
     * @param string $filename
     *
     * @return mixed
     */
    protected function store(UploadedFile $file, string $filename)
    {
        return $this->disk->put($filename, fopen($file->getRealPath(), 'r+'));
    }

    /**
     * Validate the input file.
     *
     * @param UploadedFile $file
     * @param array                                               $config
     *
     * @return bool|string
     */
    protected function fileHasError(UploadedFile $file, array $config)
    {
        $error = false;

        // 基本检查
        if (!$file->isValid()) {
            $error = $file->getError();
        } elseif ( $max_size = array_get($config, 'max_size', 0)) {
            if ( $file->getSize() > $max_size * 1024 ) {
                //$error = 'upload.ERROR_SIZE_EXCEED';
                $error = "文件大小超过限制：{$max_size} KB";
            }
        } elseif ($allow_types = array_get($config, 'allow_types')) {
            $allow_types = explode(',', $allow_types);
            $extension = $file->getClientOriginalExtension();
            if ( !in_array( $extension, $allow_types )) {
                //$error = 'upload.ERROR_TYPE_NOT_ALLOWED';
                $error = "文件类型不支持";
            }
        }

        //图片检查
        if ( $this->isImageType( $file ) ) {
            if ( $this->imageMinHasError( $file, $config) ) {
                //$error = 'upload.ERROR_LESS_THAN_MIN_SIZE';
                $error = '图片尺寸过小';
            } elseif ( $this->imageMaxHasError( $file, $config ) ){
                //$error = 'upload.ERROR_GREATER_THAN_MAX_SIZE';
                $error = '图片尺寸太大';
            }
        }

        return $error;
    }

    /**
     * Get the new filename of file.
     *
     * @param UploadedFile $file
     * @param array $config
     * @param bool $is_original_filename
     * @param string $interfere
     * @return mixed
     */
    protected function getFilename(UploadedFile $file, array $config, bool $is_original_filename = false, string $interfere = '')
    {
        $ext = '.'.$file->getClientOriginalExtension();

        $save_path = array_get( $config, 'save_path');

        $filename = !$is_original_filename ?
                    md5($file->getFilename() . $interfere).$ext :
                    $file->getClientOriginalName();

        $file_path = $this->formatPath($save_path, $filename);

        return $file_path;
    }

    /**
     * Get configuration of current action.
     *
     * @param string $key
     *
     * @return array
     */
    protected function getUploadConfig( string $key)
    {
        $config = $this->config
            ->findOrFail($key)
            ->toArray();

        $config['field_names'] = [
            'file', 'upfile'
        ];

        return $config;
    }

    /**
     * Make error response.
     *
     * @param $message
     *
     * @return string
     */
    protected function error( string $message)
    {
        return trans("ueditor::upload.{$message}");
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

    /**
     * List all files of dir. (为百度编辑器所需）
     *
     * @param string $path
     * @param int    $start
     * @param int    $size
     * @param array  $allowFiles
     *
     * @return array
     */
    public function listFiles($path, $start, $size = 20, array $allowFiles = [])
    {
        $allFiles = $this->disk->listContents($path, true);
        $files = $this->paginateFiles($allFiles, $start, $size);

        return [
            'state' => empty($files) ? 'EMPTY' : 'SUCCESS',
            'list' => $files,
            'start' => $start,
            'total' => count($allFiles),
        ];
    }

    /**
     * Split results.(为百度编辑器所需）
     *
     * @param array $files
     * @param int   $start
     * @param int   $size
     *
     * @return array
     */
    protected function paginateFiles(array $files, $start = 0, $size = 50)
    {
        return collect($files)->where('type', 'file')->splice($start)->take($size)->map(function ($file) {
            return [
                'url' => $this->getUrl($file['path']),
                'mtime' => $file['timestamp'],
            ];
        })->all();
    }
}