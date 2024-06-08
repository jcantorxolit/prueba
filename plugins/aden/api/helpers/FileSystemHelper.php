<?php

namespace AdeN\Api\Helpers;

use AdeN\Api\Classes\FileResponse;

/**
 * String helper functions
 */
class FileSystemHelper
{
    public static function attachInstance($file)
    {
        try {
            if ($file == null || !($file instanceof \System\Models\File)) {
                return null;
            }

            $attachment = new FileResponse($file);

            return $attachment;
        } catch (\Exception $ex) {
            //throw $th;
        }
        return null;
    }
}
