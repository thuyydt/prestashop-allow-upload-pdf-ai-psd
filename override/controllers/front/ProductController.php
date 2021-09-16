<?php

use function React\Promise\all;

class ProductController extends ProductControllerCore
{
    protected function pictureUpload()
    {
        if (!$field_ids = $this->product->getCustomizationFieldIds()) {
            return false;
        }
        $authorized_file_fields = [];
        foreach ($field_ids as $field_id) {
            if ($field_id['type'] == Product::CUSTOMIZE_FILE) {
                $authorized_file_fields[(int) $field_id['id_customization_field']] = 'file' . (int) $field_id['id_customization_field'];
            }
        }
        $indexes = array_flip($authorized_file_fields);

        foreach ($_FILES as $field_name => $file) {
            if (in_array($field_name, $authorized_file_fields) && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
                $file_name = md5(uniqid(mt_rand(0, mt_getrandmax()), true));
                if ($error = ImageManager::validateUpload($file, (int) Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE'))) {
                    $this->errors[] = $error;
                }

                $product_picture_width = (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH');
                $product_picture_height = (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT');
                $tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');

                if ($error || (!$tmp_name || !move_uploaded_file($file['tmp_name'], $tmp_name))) {
                    return false;
                }

                if ($file['type'] == 'application/pdf' || $file['type'] == 'application/illustrator' || $file['type'] == 'application/postscript') {
                    if ($file['type'] == 'application/illustrator' || $file['type'] == 'application/postscript') {
                        $type = 'ai';
                    } else {
                        $type = 'pdf';
                    }

                    $content_extra_file = Tools::file_get_contents($tmp_name);
                    if (empty($content_extra_file)) {
                        $this->errors[] = $this->trans('An error occurred during the image upload process.', [], 'Shop.Notifications.Error');
                    } else {
                        $return_erro = file_put_contents(_PS_UPLOAD_DIR_.$file_name.''.'.'.$type, $content_extra_file);
                        if (!$return_erro || $return_erro <= 0) {
                            $this->errors[] = $this->trans('An error occurred during the image upload process.', [], 'Shop.Notifications.Error');
                        } else {
                            $this->context->cart->addPictureToProduct($this->product->id, $indexes[$field_name], Product::CUSTOMIZE_FILE, $file_name);
                        }
                    }
                } else {
                    if (!ImageManager::resize($tmp_name, _PS_UPLOAD_DIR_ . $file_name)) {
                        $this->errors[] = $this->trans('An error occurred during the image upload process.', [], 'Shop.Notifications.Error');
                    } elseif (!ImageManager::resize($tmp_name, _PS_UPLOAD_DIR_ . $file_name . '_small', $product_picture_width, $product_picture_height)) {
                        $this->errors[] = $this->trans('An error occurred during the image upload process.', [], 'Shop.Notifications.Error');
                    } else {
                        $this->context->cart->addPictureToProduct($this->product->id, $indexes[$field_name], Product::CUSTOMIZE_FILE, $file_name);
                    }
                }
                unlink($tmp_name);
            }
        }

        return true;
    }
}
