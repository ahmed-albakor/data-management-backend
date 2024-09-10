<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image;

class ImageService
{
    /**
     * تخزين الصورة بعد ضغطها وتقليل حجمها
     *
     * @param  mixed  $file
     * @param  string $path
     * @param  int    $width
     * @param  int    $quality
     * @return string
     */
    public static function storeImage($file, $path, $width = 1000, $quality = 80)
    {
        // إنشاء مسار الصورة
        $imageName = time() . '.' . $file->getClientOriginalExtension();

        // تحميل الصورة واستخدام Intervention Image لضغطها وتغيير حجمها
        $image = Image::make($file)
            ->resize($width, null, function ($constraint) {
                $constraint->aspectRatio(); // الحفاظ على نسبة العرض إلى الارتفاع
                $constraint->upsize(); // عدم تكبير الصورة أكثر من اللازم
            })
            ->encode($file->getClientOriginalExtension(), $quality); // ضبط الجودة

        // تخزين الصورة في الـ Storage
        Storage::put($path . '/' . $imageName, (string) $image);

        return $imageName;
    }

    /**
     * تحديث الصورة الموجودة (حذف القديمة وحفظ الجديدة)
     *
     * @param  mixed  $file
     * @param  string $path
     * @param  string $oldImage
     * @param  int    $width
     * @param  int    $quality
     * @return string
     */
    public static function updateImage($file, $path, $oldImage = null, $width = 1000, $quality = 80)
    {
        // حذف الصورة القديمة إذا كانت موجودة
        if ($oldImage) {
            ImageService::deleteImage($path, $oldImage);
        }

        // حفظ الصورة الجديدة
        return  ImageService::storeImage($file, $path, $width, $quality);
    }

    /**
     * حذف الصورة من التخزين
     *
     * @param  string  $path
     * @param  string  $imageName
     * @return bool
     */
    public static function deleteImage($path, $imageName)
    {
        if (Storage::exists($path . '/' . $imageName)) {
            return Storage::delete($path . '/' . $imageName);
        }

        return false;
    }
}
