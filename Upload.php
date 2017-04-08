<?php 

namespace alkurn\upload;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class Upload extends Model
{
    public $file;
    public $files;
    public $uploadsAlias =  '/uploads';

    public function upload($model, $field)
    {
        $this->file = UploadedFile::getInstance($model, $field);

        if ($this->file){

            $this->file->name = Yii::$app->security->generateRandomString(). '.' . $this->file->extension;
            $path = $this->uploadsAlias . '/' . $this->getBaseName();
            $name = $this->file->baseName . '.' . $this->file->extension;

            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $this->file->saveAs($path . $name);    
            return $this->getBaseName() . $name;
        } else {
            return $model->getOldAttribute($field);
        }
    }
    
    public function getBaseName()
    {
        return chunk_split(substr(preg_replace('/[^A-Za-z0-9\-]/', '', $this->file->baseName), 0, 8), 1, '/');
    }
    
    public function createBasePath($path)
    {
        return chunk_split(substr(preg_replace('/[^A-Za-z0-9\-]/', '', $path), 0, 8), 1, '/');
    }
}


?>