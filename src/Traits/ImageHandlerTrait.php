<?php

namespace Landers\LaravelUpload\Traits;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Intervention\Image\Image as ImageClass;
use Intervention\Image\Gd\Font;

/**
 * Trait ImageHandlerTrait
 * @package Landers\LaravelUpload\Traits
 * @property Filesystem $disk
 */
trait ImageHandlerTrait
{
    /**
     * 取得字体文件
     * @return string
     */
    private function getMarkerDefaultFont( )
    {
        return dirname(__DIR__) . '/Resources/arial.ttf';
    }

    /**
     * 取得遮罩图
     * @param string|null $img
     * @return string|null
     */
    private function getMarkerImage( string $img = null )
    {
        if ( !$img ) return null;

        $img_file = base_path($img);
        if ( !is_file($img_file) ) {
            $img_file = dirname(__DIR__) . "/Resources/{$img}";
        }

        return is_file($img_file) ? $img_file : null;
    }

    /**
     * 保存资源
     * @param ImageClass $image
     * @param string $filename
     * @param bool $is_complete
     * @return int
     */
    private function saveImageResource( ImageClass $image, string $filename, bool $is_complete = false )
    {
        $resource = $image->encode(null, 86);
        $result = $this->disk->put($filename, $resource);
        if ( $is_complete ) {
            $image->response('jpeg')->send();
            $image->destroy();
            die;
        } else {
            return $result;
        }
    }

    /**
     * @param $image
     * @return array
     */
    private function getImageSize( ImageClass $image )
    {
        return [
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }


    /**
     * 如果是图片的话进行处理
     * @param UploadedFile $file
     * @param array $config
     * @param array $result
     */
    protected function storeWithIfHandleImage( UploadedFile $file, array $config, array &$result )
    {
        // 原始上传图片
        $original_filename = array_get( $result, 'filename');
        $resource = fopen($file->getRealPath(), 'r+');
        $type = array_get($result, 'type');
        if ( strpos($type,'image') === false ) {
            return;
        }

        $image = Image::make( $resource );
        $image->backup('original');

        // 处理原图 - 生成水印文字

        $img_is_mark_text = (bool)((int)array_get( $config, 'img_is_mark_text' ));
        $img_mark_text = (string)array_get( $config, 'img_mark_text' );
        if ( $img_is_mark_text && $img_mark_text) {
            $img_mark_text = str_replace('{DOMAIN}', config('app.url'), $img_mark_text);
            $img_mark_text_position = (int)array_get( $config, 'img_mark_text_position');
            $img_mark_text_font_size = (int)array_get( $config, 'img_mark_text_font_size');
            $img_mark_text_font_color = (string)array_get( $config, 'img_mark_text_font_color');
            $img_mark_text_font_family = (string)array_get( $config, 'img_mark_text_font_family');
            $img_mark_text_margin = (int)array_get( $config, 'img_mark_text_margin');
            $img_mark_text_angle = (int)array_get( $config, 'img_mark_text_angle');
            $img_mark_text_offset_x = (int)array_get( $config, 'img_mark_text_offset_x');
            $img_mark_text_offset_y = (int)array_get( $config, 'img_mark_text_offset_y');
            $this->buildMarkWarterWithText($image,
                $img_mark_text, $img_mark_text_position, $img_mark_text_font_size,
                $img_mark_text_font_color, $img_mark_text_font_family,
                $img_mark_text_margin, $img_mark_text_angle,
                $img_mark_text_offset_x, $img_mark_text_offset_y
            );

        }


        // 处理原图 - 生成水印图
        $img_is_mark_img = (bool)((int)array_get( $config, 'img_is_mark_img' ));
        if ( $img_is_mark_img ) {
            $img_mark_img = (string)array_get( $config, 'img_mark_img' );
            $img_mark_img_position = (int)array_get( $config, 'img_mark_img_position');
            $img_mark_img_margin = (int)array_get( $config, 'img_mark_img_margin' );
            $img_mark_img_offset_x = (int)array_get( $config, 'img_mark_img_offset_x');
            $img_mark_img_offset_y = (int)array_get( $config, 'img_mark_img_offset_y');
            $this->buildMarkWarterWithImage($image,
                $img_mark_img, $img_mark_img_position,
                $img_mark_img_margin, $img_mark_img_offset_x,
                $img_mark_img_offset_y
            );
        }

        // 保存原图
        $this->saveImageResource( $image, $original_filename );

        // 生成小图(缩略图)
        $image->reset('original');
        if ( array_get( $config, 'img_is_small' ) ) {
            $img_small_width = array_get( $config, 'img_small_width' );
            $img_small_height = array_get( $config, 'img_small_height' );
            $img_small_mode = array_get( $config, 'img_small_mode' );
            if ( $this->buildSmall( $image, $img_small_mode,  $img_small_width, $img_small_height) ) {
                $small_filename = $this->getFilename( $file, $config, false, 'small' );
                $this->saveImageResource( $image, $small_filename );
                $result['url_small'] = $this->getUrl( $small_filename );
            }
        }

        // 生成迷你图
        if ( array_get( $config, 'img_is_small_mini' ) ) {
            $url_small = array_get($result, 'url_small');
            if ($url_small && $url_small !== array_get($result, 'url')) {
                if ($this->buildMini($image)) {
                    $mini_filename = $this->getFilename($file, $config, false, 'mini');
                    $this->saveImageResource($image, $mini_filename);
                    $result['url_mini'] = $this->getUrl($mini_filename);
                }
            }
        }

        // 删除原图
        $this->deleteOriginal( $config, $result );
    }


    /**
     * 生成小图(缩略图)
     * @param $image
     * @param array $config
     * @return bool|string
     */
    protected function buildSmall( ImageClass $image, string $mode,  int $width, int $height )
    {
        $dist_size = [
            'width' => $width,
            'height' => $height
        ];

        // 取得原图尺寸
        $original_size = $this->getImageSize( $image );

        $is_scale = $mode == 'SCALE';

        if ( $is_scale ) {
            // 计算目标图尺寸
            $size = $this->zoomSize( $is_scale, $original_size, $dist_size );

            // 设整图片大小
            $image->resize($size['width'], $size['height']);

        } else {
            // 计算目标图尺寸
            $tmp_size = $this->zoomSize( true, $original_size, $dist_size, false );

            // 设整图片大小
            $image->resize($tmp_size['width'], $tmp_size['height']);

            // 裁剪
            $image->crop($dist_size['width'], $dist_size['height']);

        }
        return true;
    }

    /**
     * 生成迷你图
     * @param ImageClass $image
     * @param int $dist_size
     * @return bool
     */
    protected function buildMini( ImageClass $image, int $dist_size = 100 )
    {
        // 取得原图尺寸
        $image_size = $this->getImageSize( $image );

        if ( $image_size['width'] > $image_size['height'] ) {
            $image->heighten($dist_size);
        } else {
            $image->widen($dist_size);
        }

        $image->crop($dist_size, $dist_size);

        return true;
    }

    /**
     * 删除原图
     * @param array $config
     * @param $result
     * @return bool
     */
    protected function deleteOriginal( array $config, &$result )
    {
        $img_is_small = array_get( $config, 'img_is_small', false );
        $img_small_is_delete_original = array_get( $config, 'img_small_is_delete_original', false );
        if ( $img_is_small && $img_small_is_delete_original ) {
            $this->disk->delete( $result['filename'] );
            $result['url'] = $result['url_small'];
        }

        return true;
    }

    /**
     * 用水印图生成水印效
     * @param ImageClass $image
     * @param array $config
     * @return bool
     */
    protected function buildMarkWarterWithImage(
        ImageClass $image,
        string $img,
        int $position,
        int $margin,
        int $offset_x,
        int $offset_y
    ){
        if (!$mask_image = $this->getMarkerImage( $img )){
            return false;
        }

        // 水印位置
        $positions = [
            '1' => 'top-left',
            '2' => 'top',
            '3' => 'top-right',
            '4' => 'left',
            '5' => 'center',
            '6' => 'right',
            '7' => 'bottom-left',
            '8' => 'bottom',
            '9' => 'bottom-right',
        ];
        $position = $positions[(string)$position];

        $offset_x = $margin + $offset_x;
        $offset_y = $margin + $offset_y;

        $image->insert( $mask_image, $position, $offset_x, $offset_y );
        return true;
    }

    /**
     * 用文本生成水印效
     * @param ImageClass $image
     * @param array $config
     * @return bool|string
     */
    protected function buildMarkWarterWithText(
        ImageClass $image,
        string $text,
        int $position,
        int $size,
        string $color,
        string $family,
        int $margin,
        int $angle,
        int $offset_x,
        int $offset_y
    ){
        // 取得原图信息
        $this->buildMarkTextWarter(
            $image, $text, $position, $size, $color, $family, $margin, $offset_x, $offset_y, $angle
        );

        $color = [0, 0, 0, array_get(explode(',', $color), 3, 0.3)];
        $this->buildMarkTextWarter(
            $image, $text, $position, $size, $color, $family, $margin, $offset_x-1, $offset_y-1, $angle
        );

        return true;
    }

    /**
     * 在指定的image对象上生成文字水印
     *
     * @param ImageClass $image
     * @param string $text
     * @param int $position
     * @param int $font_size
     * @param $font_color
     * @param int $margin
     * @param array $offset
     * @param int $text_angle
     * @throws \Exception
     */
    private function buildMarkTextWarter(
        ImageClass &$image,
        string $text,
        int $position,
        int $font_size,
        $font_color,
        $font_family,
        int $margin,
        int $offset_x,
        int $offset_y,
        int $text_angle
    ){
        $position or $position = 9;
        $font_size or $font_size = 20;
        $margin or $margin = 0;
        $text_angle or $text_angle = 0;
        $font_color or $font_color = '255, 255, 255, 0.3';
        if ( is_string($font_color) ) $font_color = explode(',', $font_color );

        if ( count($font_color) != 4 ) {
            throw new \Exception('颜色值异常');
        }

        //dp([
        //    compact('position', 'font_size', 'margin', 'offset', 'text_angle', 'font_color')
        //]);

        // 原图尺寸
        $original_size = $this->getImageSize( $image );

        // 水印文字字体文件
        $font_file = $font_family ? base_path($font_family) : $this->getMarkerDefaultFont();

        // 水印文字尺寸
        $marker_size = $this->getTextMarkerSize(
            $text,
            $font_size,
            $text_angle,
            $font_file
        );

        // 水印位置
        $marker_position = $this->getMarkerPosition(
            $original_size,
            $marker_size,
            $position,
            $margin
        );

        // 水印位置偏移
        $position_x = $marker_position['x'] + $offset_x;
        $position_y = $marker_position['y'] + $offset_y;

        // 写入水印
        $image->text($text, $position_x, $position_y, function(Font $font) use (
            $font_file,
            $font_size,
            $font_color,
            $text_angle
        ) {
            $font->file($font_file);
            $font->size($font_size);
            $font->align('center');
            $font->valign('middle');
            $font->color($font_color);
            $font->angle($text_angle);
        });
    }

    /**
     * 图片最小尺寸是否错误
     *
     * @param UploadedFile $file
     * @param array $config
     * @return bool
     */
    protected function imageMinHasError( UploadedFile $file, array $config )
    {
        $img_min_width = array_get($config, 'img_min_width', false);
        $img_min_height = array_get($config, 'img_min_height', false);

        if ($img_min_width && $img_min_height) {
            $fso = app(Filesystem::class);
            $content = $fso->get($file->getPathname());
            $image = Image::make($content);
            if (
                $image->width() < $img_min_width ||
                $image->height() < $img_min_height
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 图片最小尺寸是否错误
     * @param UploadedFile $file
     * @param array $config
     * @return bool
     */
    protected function imageMaxHasError( UploadedFile $file, array $config )
    {
        $img_max_width = array_get( $config, 'img_max_width', false );
        $img_max_height = array_get( $config, 'img_max_height', false );
        if ($img_max_width && $img_max_height) {
            $fso = app(\Illuminate\Filesystem\Filesystem::class);
            $content = $fso->get($file->getPathname());
            $image = Image::make($content);
            if (
                $image->width() > $img_max_width ||
                $image->height() > $img_max_height
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 计算水印文字的尺寸
     * @param $text
     * @param int $font_size
     * @param int $angle
     * @param null $font_file
     * @return array
     */
    private function getTextMarkerSize($text, $font_size = 12, $angle = 0, $font_file)
    {
        if ( !$text ) return [
            'width' => 0,
            'height' => 0
        ];

        /*
         * $a是个8个元素的数组，如下：
         * 6,7(左上)		4,5(右上)
         * 0,1(左下)		2,3(右下)
         */
        $a = imagettfbbox($font_size, $angle, $font_file, $text);

        $width = abs($a[0] - $a[2]);
        $height = abs($a[7] - $a[1]);

        $width = round( $width / 1.3 );
        $height = round( $height / 1.2 );

        return compact('width', 'height');
    }

    /**
     * 计算水印位置
     * @param array $img_size
     * @param array $mark_size
     * @param int $position
     * @param int $margin
     * @return array|int
     */
    private function getMarkerPosition(
        array $img_size,
        array $mark_size,
        int $position = 9,
        int $margin = 0
    ){
        if (is_array($position)) return $position;

        // 系数调整
        $k = 5;

        $_w = abs($img_size['width'] - $mark_size['width']);
        $_h = abs($img_size['height'] - $mark_size['height']);
        $x1	= $margin;
        $x2 = $_w / 2;
        $x3 = $_w - $margin;
        $y1	= $margin;
        $y2 = $_h / 2;
        $y3 = $_h - $margin;

        switch( $position ){
            case 1 : $x = $x1; $y = $y1; break;
            case 2 : $x = $x2; $y = $y1; break;
            case 3 : $x = $x3 - $k; $y = $y1; break;
            case 4 : $x = $x1; $y = $y2; break;
            case 5 : $x = $x2; $y = $y2; break;
            case 6 : $x = $x3 - $k; $y = $y2; break;
            case 7 : $x = $x1; $y = $y3 - $k; break;
            case 8 : $x = $x2; $y = $y3 - $k; break;
            case 9 :
            default: $x = $x3 - $k; $y = $y3 - $k; break;
        };

        // 系统自身导致的固定错误纠正
        $x += round($mark_size['width'] / 2);
        $y += round($mark_size['height'] / 2);

        return ['x' => $x, 'y' => $y];
    }

    /**
     * 缩放比例
     * @param bool $is_scale
     * @param array $original_size
     * @param array $dist_size
     * @param bool $is_inner
     * @return array
     */
    private function zoomSize(bool $is_scale, array $original_size, array $dist_size, bool $is_inner = true){
        $w1 = $original_size['width'];
        $h1 = $original_size['height'];
        $w2 = $dist_size['width'];
        $h2 = $dist_size['height'];

        if ( $is_scale ) {
            $w = null;
            $h = null;
            if ($w2 > 0 && $h2 > 0) {
                if ($is_inner) {
                    if ($w1 / $h1 >= $w2 / $h2) {
                        if ($w1 > $w2) {
                            $w = $w2;
                            $h = ($w2 * $h1) / $w1;
                        } else {
                            $w = $w1;
                            $h = $h1;
                        }
                    } else {
                        if ($h1 > $h2) {
                            $h = $h2;
                            $w = ($w1 * $h2) / $h1;
                        } else {
                            $w = $w1;
                            $h = $h1;
                        }
                    }
                } else {
                    $k1 = $w1 / $h1;
                    $k2 = $w2 / $h2;
                    if ($k1 > $k2) {
                        $h = $h2;
                        $w = $h2 * $k1;
                    } else {
                        $w = $w2;
                        $h = $w2 / $k1;
                    }
                }
            } else {
                if ($w2 > 0) {
                    $w = $w2;
                    $h = ($w2 * $h1) / $w1;
                }
                if ($h2 > 0) {
                    $h = $h2;
                    $w = ($w1 * $h2) / $h1;
                }
            }
            return [
                'width' => round($w),
                'height' => round($h)
            ];
        } else {
            if ( $w2 > 0 && $h2 > 0 ) {
                return $dist_size;
            } else {
                return $original_size;
            }
        }
    }
}