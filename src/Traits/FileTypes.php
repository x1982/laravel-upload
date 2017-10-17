<?php
namespace Landers\LaravelUpload\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;


trait FileTypes
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
    private function isImageType(UploadedFile $file)
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
    private function isAudioType(UploadedFile $file)
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
    private function isVideoType(UploadedFile $file)
    {
        return $this->matchFileType($file, [
            'mp4'
        ]);
    }

}