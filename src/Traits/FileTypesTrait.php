<?php
namespace Landers\LaravelUpload\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;


trait FileTypesTrait
{
    /**
     * 匹配文件类型
     * @param UploadedFile $file
     * @param array $types
     * @return bool
     */
    private function matchFileType(UploadedFile $file, array $types)
    {
        $extension = $file->getClientOriginalExtension();
        return in_array($extension, $types);
    }

    /**
     * 是否是图像类型
     * @param UploadedFile $file
     * @param array $types
     */
    protected function isImageType(UploadedFile $file)
    {
        return $this->matchFileType($file, [
            'jpg', 'jpeg', 'gif', 'png', '.bmp'
        ]);
    }

    /**
     * 是否为音频文件
     * @param UploadedFile $file
     * @return bool
     */
    protected function isAudioType(UploadedFile $file)
    {
        return $this->matchFileType($file, [
            'mp3'
        ]);
    }

    /**
     * 是否为视频频文件
     * @param UploadedFile $file
     * @return bool
     */
    protected function isVideoType(UploadedFile $file)
    {
        return $this->matchFileType($file, [
            'mp4'
        ]);
    }

}