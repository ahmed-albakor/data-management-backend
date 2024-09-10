<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageService
{
    /**
     * إنشاء المجلد إذا لم يكن موجودًا
     *
     * @param  string  $folderName
     * @return void
     */
    private static function makeFolder($folderName)
    {
        // تحديد مسار المجلد
        $pathFolder = storage_path(sprintf('app/public/%s', $folderName));

        // التحقق من وجود المجلد، وإنشاءه إذا لم يكن موجودًا
        if (!File::isDirectory($pathFolder)) {
            File::makeDirectory($pathFolder, 0755, true);
        }
    }

    /**
     * تحسين الصورة باستخدام Spatie Image Optimizer
     *
     * @param  string  $imagePath
     * @return void
     */
    private static function optimizeImage($imagePath)
    {
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($imagePath);
    }

    /**
     * تحويل الصورة إلى WebP باستخدام GD Library
     *
     * @param  mixed  $image
     * @param  string  $newPath
     * @return void
     */
    private static function convertToWebP($image, $newPath)
    {
        // التحقق من نوع الصورة (JPEG/PNG) واستخدام GD Library لتحويلها إلى WebP
        $imageInfo = getimagesize($image->getPathname());
        $mimeType = $imageInfo['mime'];

        if ($mimeType == 'image/jpeg') {
            $source = imagecreatefromjpeg($image->getPathname());
        } elseif ($mimeType == 'image/png') {
            $source = imagecreatefrompng($image->getPathname());
        } else {
            throw new \Exception("Unsupported image type");
        }

        // تحويل الصورة إلى WebP وحفظها في المسار المحدد
        imagewebp($source, $newPath, 90); // جودة الصورة 90%
        imagedestroy($source);
    }

    /**
     * تخزين الصورة في المسار المحدد وتحويلها إلى WebP وتحسينها
     *
     * @param  mixed  $image
     * @param  string  $folder
     * @return string
     */
    public static function storeImage($image, $folder)
    {
        // إنشاء المجلد إن لم يكن موجودًا
        self::makeFolder($folder);

        // إنشاء اسم جديد للصورة مع استخدام امتداد WebP
        $imageName = time() . '.webp';

        // تحديد المسار الجديد للصورة
        $newPath = storage_path(sprintf('app/public/%s/%s', $folder, $imageName));

        // تحويل الصورة إلى WebP وتخزينها
        self::convertToWebP($image, $newPath);

        // تحسين الصورة باستخدام Spatie Image Optimizer
        self::optimizeImage($newPath);

        // إرجاع المسار النسبي للصورة
        return sprintf('%s/%s', $folder, $imageName);
    }

    /**
     * تحديث الصورة عن طريق حذف الصورة القديمة وتخزين الجديدة وتحسينها
     *
     * @param  mixed  $image
     * @param  string  $folder
     * @param  string|null  $oldImageName
     * @return string|null
     */
    public static function updateImage($image, $folder, $oldImageName) : ?string
    {
        // حذف الصورة القديمة إذا كانت موجودة
        if ($oldImageName && Storage::exists("public/" . $oldImageName)) {
            Storage::delete("public/" . $oldImageName);
        }

        // تخزين وتحسين الصورة الجديدة
        return self::storeImage($image, $folder);
    }
}
