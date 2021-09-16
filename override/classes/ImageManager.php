<?php

class ImageManager extends ImageManagerCore
{
    const MIME_TYPE_SUPPORTED = [
        'image/gif',
        'image/jpg',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'application/pdf',          // extra for pdf
        'application/illustrator',  // extra for ai
        'application/postscript'    // extra for ai
    ];

    public static function validateUpload($file, $maxFileSize = 0, $types = null, $mimeTypeList = null)
    {
        if ((int) $maxFileSize > 0 && $file['size'] > (int) $maxFileSize) {
            return Context::getContext()->getTranslator()->trans('Image is too large (%1$d kB). Maximum allowed: %2$d kB', [$file['size'] / 1024, $maxFileSize / 1024], 'Admin.Notifications.Error');
        }

        if (!ImageManager::isRealImage($file['tmp_name'], $file['type'], $mimeTypeList) || !ImageManager::isCorrectImageFileExt($file['name'], $types) || preg_match('/\%00/', $file['name'])) {
            return Context::getContext()->getTranslator()->trans('Image format not recognized, allowed formats are: .gif, .jpg, .png, .ai, .pdf', [], 'Admin.Notifications.Error');
        }
        if ($file['error']) {
            return Context::getContext()->getTranslator()->trans('Error while uploading image; please change your server\'s settings. (Error code: %s)', [$file['error']], 'Admin.Notifications.Error');
        }

        return false;
    }

    public static function isCorrectImageFileExt($filename, $authorizedExtensions = null)
    {
        // Filter on file extension
        if ($authorizedExtensions === null) {
            $authorizedExtensions = ['gif', 'jpg', 'jpeg', 'jpe', 'png', 'pdf', 'ai'];
        }
        $nameExplode = explode('.', $filename);
        if (count($nameExplode) >= 2) {
            $currentExtension = strtolower($nameExplode[count($nameExplode) - 1]);
            if (!in_array($currentExtension, $authorizedExtensions)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    public static function resize(
        $sourceFile,
        $destinationFile,
        $destinationWidth = null,
        $destinationHeight = null,
        $fileType = 'jpg',
        $forceType = false,
        &$error = 0,
        &$targetWidth = null,
        &$targetHeight = null,
        $quality = 5,
        &$sourceWidth = null,
        &$sourceHeight = null
    ) {
        clearstatcache(true, $sourceFile);

        if (!file_exists($sourceFile) || !filesize($sourceFile)) {
            return !($error = self::ERROR_FILE_NOT_EXIST);
        }

        if ($fileType == 'ai' || $fileType == 'pdf') {
            Hook::exec('actionOnImageResizeAfter', ['dst_file' => $destinationFile, 'file_type' => $fileType]);
            return true;
        } else {
            return parent::resize($sourceFile, $destinationFile, $destinationWidth, $destinationHeight, $fileType, $forceType, $error, $targetWidth, $targetHeight, $quality, $sourceWidth, $sourceHeight);
        }
    }
}