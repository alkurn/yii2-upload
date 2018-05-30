<?php

namespace alkurn\upload;
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class Upload extends Model
{
    public $file;
    public $files;
    public $uploadsAlias =  '/uploads/storage';

    public function upload($model, $field)
    {
        $this->file = UploadedFile::getInstance($model, $field);

        if ($this->file){

            $this->file->name = Yii::$app->security->generateRandomString(). '.' . $this->file->extension;
            $baseName = $this->file->baseName;
            $path = $this->uploadsAlias . '/' . $this->getBaseName( $baseName );
            $name = $this->file->baseName . '.' . $this->file->extension;

            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $this->file->saveAs($path . $name);
            return $this->getBaseName( $baseName ) . $name;
        } else {
            return $model->getOldAttribute($field);
        }
    }

    public function uploadByName($model, $field)
    {
        $this->file = UploadedFile::getInstanceByName($field);

        if ($this->file){

            $this->file->name = Yii::$app->security->generateRandomString(). '.' . $this->file->extension;
            $baseName = $this->file->baseName;
            $path = $this->uploadsAlias . '/' . $this->getBaseName( $baseName );
            $name = $this->file->baseName . '.' . $this->file->extension;

            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $this->file->saveAs($path . $name);
            return $this->getBaseName( $baseName ) . $name;
        } else {
            return $model->getOldAttribute($field);
        }
    }

    public function uploadMultiple($model, $field)
    {
        $files = [];
        $this->files = UploadedFile::getInstances($model, $field);
        if ($this->files) {
            foreach ($this->files as $file) {

                if ($file) {
                    $file->name = Yii::$app->security->generateRandomString() . '.' . $file->extension;

                    $path = $this->uploadsAlias . '/' . $this->getBaseName( $file->baseName );
                    $name = $file->baseName . '.' . $file->extension;

                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $file->saveAs($path . $name);
                    $files[] = $this->getBaseName($file->baseName) . $name;
                } else {
                    $files[] = $model->getOldAttribute($field);
                }
            }
        }

        return $files;
    }

    public function uploadMultipleByName($field)
    {
        $files = [];
        $this->files = UploadedFile::getInstancesByName($field);

        if ($this->files) {

            foreach ($this->files as $file) {
                if ($file) {
                    $file->name = Yii::$app->security->generateRandomString() . '.' . $file->extension;

                    $path = $this->uploadsAlias . '/' . $this->getBaseName( $file->baseName );
                    $name = $file->baseName . '.' . $file->extension;

                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $file->saveAs($path . $name);
                    $files[] = $this->getBaseName($file->baseName) . $name;
                }
            }
        }

        return $files;
    }

    public function uploadWithUrl($url)
    {
        if(!filter_var($url, FILTER_VALIDATE_URL) || !file_exists($url)){
            return false;
        }

        $options = self::extendOptions(['url' => $url]);
        $baseName = Yii::$app->security->generateRandomString();
        $path = $this->uploadsAlias . '/' . $this->getBaseName( $baseName );
        $name = $baseName . '.' . $options['extension'];
        $name .= ( !in_array($options['extension'], ['png','jpg','jpeg','gif']) ) ? '.png' : '';
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        copy($options['url'], $path . $name);
        return $this->getBaseName( $baseName ) . $name;
    }

    protected static function extendOptions(array $options)
    {
        $parsedUrl = parse_url($options['url']);
        $headers = get_headers($options['url'], 1);
        if (!$parsedUrl || !$headers || !preg_match('/^(HTTP)(.*)(200)(.*)/i', $headers[0])) {
            $options['error'] = UPLOAD_ERR_NO_FILE;
        }
        $options['name'] = isset($parsedUrl['path']) ? pathinfo($parsedUrl['path'], PATHINFO_BASENAME) : '';
        $options['baseName'] = isset($parsedUrl['path']) ? pathinfo($parsedUrl['path'], PATHINFO_FILENAME) : '';
        $options['extension'] = isset($parsedUrl['path'])
            ? mb_strtolower(pathinfo($parsedUrl['path'], PATHINFO_EXTENSION))
            : '';
        $options['size'] = isset($headers['Content-Length']) ? $headers['Content-Length'] : 0;
        $options['type'] = isset($headers['Content-Type'])
            ? $headers['Content-Type']
            : FileHelper::getMimeTypeByExtension($options['name']);
        return $options;
    }

    public function getBaseName($baseName = null)
    {
        return chunk_split(substr(preg_replace('/[^A-Za-z0-9\-]/', '', $baseName), 0, 8), 1, '/');
    }

    public function createBasePath($path)
    {
        return chunk_split(substr(preg_replace('/[^A-Za-z0-9\-]/', '', $path), 0, 8), 1, '/');
    }
}


?>