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
     * تخزين الصورة في المسار المحدد وتحسينها
     *
     * @param  mixed  $image
     * @param  string  $folder
     * @return string
     */
    public static function storeImage($image, $folder)
    {
        // إنشاء المجلد إن لم يكن موجودًا
        self::makeFolder($folder);

        // الحصول على امتداد الصورة وإنشاء اسم جديد باستخدام الوقت الحالي
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        // تحديد المسار الجديد للصورة
        $newPath = storage_path(sprintf('app/public/%s/%s', $folder, $imageName));

        // نقل الصورة إلى المسار الجديد
        move_uploaded_file($image->getPathname(), $newPath);

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
