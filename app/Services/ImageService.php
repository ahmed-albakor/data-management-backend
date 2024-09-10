<?php


namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class ImageService
{


    private static function MakeFolder($folderName)
    {
        $pathFolder = storage_path(sprintf('app/public/%s', $folderName));
        if (!File::isDirectory($pathFolder)) {
            File::makeDirectory($pathFolder);
        }
    }

    public static function storeImage($image, $folder)
    {
        self::MakeFolder($folder);
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $new_path = storage_path(sprintf('app/public/%s/%s', $folder, $imageName));
        move_uploaded_file($image, $new_path);
        return sprintf('%s/%s', $folder, $imageName);
    }
    public static function updateImage($image, $folder, $oldImageName) : string | null
    {
       
     return Storage::delete("public/" . $oldImageName) ? ImageService::storeImage($image, $folder) : null;
        

    }
}
