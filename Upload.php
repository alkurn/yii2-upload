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
    public $uploadsAlias = '/uploads/storage';
    public $uploadsModel;

    public function upload($field, $model = null, $is_save = true)
    {
        $this->file = is_null($model) ?
            UploadedFile::getInstanceByName($field) :
            UploadedFile::getInstance($model, $field);

        if ($this->file) {
            $this->file->name = Yii::$app->security->generateRandomString() . '.' . $this->file->extension;
            $baseName = $this->file->baseName;
            $path = $this->uploadsAlias . '/' . $this->getBaseName($baseName);
            $name = $this->file->baseName . '.' . $this->file->extension;

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            if ($this->file->saveAs($path . $name)) {
                return $this->saveToMedia($this->file, $is_save);
            }
            return false;

        } else {
            return $model ? $model->getOldAttribute($field) : false;
        }
    }

    public function uploadMultiple($field, $model = null, $is_save = true)
    {
        $files = [];
        $this->files = is_null($model) ? UploadedFile::getInstancesByName($field) : UploadedFile::getInstances($model, $field);
        if ($this->files) {
            foreach ($this->files as $file) {

                if ($file) {
                    $file->name = Yii::$app->security->generateRandomString() . '.' . $file->extension;
                    $path = $this->uploadsAlias . '/' . $this->getBaseName($file->baseName);
                    $name = $file->baseName . '.' . $file->extension;

                    if (!is_dir($path)) {
                        mkdir($path, 0755, true);
                    }
                    if ($file->saveAs($path . $name)) {
                        $files[] = $this->saveToMedia($file, $is_save);
                    }

                } else {
                    $files[] = $model->getOldAttribute($field);
                }
            }
        }
        return $files;
    }

    public function videoThumb($srcFile){

        $filename = Yii::$app->security->generateRandomString() . '.jpg';
        $path = $this->uploadsAlias . $this->getBaseName($filename);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $destPath = $path .'/' . $filename;
        $output = array();
        $cmd = "/usr/bin/ffmpeg -i '$srcFile' -an -ss 00:00:05 -r 1 -vframes 1 -y '$destPath'";
        exec($cmd, $output, $retval);
        if ($retval) {
            @unlink($destPath);
            return false;
        }
        if($data = getimagesize($destPath)){
            $model = new $this->uploadsModel;
            $model->filename = $filename;
            $model->filesize = $data[0] * $data[1];
            $model->path = $path;
            $model->mimetype = $data['mime'];
            return $model->save() ? $model->id : null;
        }
        return null;
    }

    public function awsUpload($keyName, $filePath, $ACL = 'public-read')
    {

        /*
         $activated = Yii::$app->config->get('activated-AwsS3');
         if($activated == 'Yes' && Yii::$app->s3){*/
        if(isset(Yii::$app->s3) && Yii::$app->s3){
            return Yii::$app->s3->putObject($keyName, $filePath, 'public-read');
        }


        /*try {
            $aws = Yii::$app->s3->getS3()->createS3();
            $result = $aws->putObject(array(
                'Bucket' => Yii::$app->s3->bucket,
                'Key' => $keyName,
                'SourceFile' => $filePath,
                'ACL' => 'public-read',
                'StorageClass' => 'REDUCED_REDUNDANCY',
            ));
            return $result;
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
            exit;
        }*/

        /*}*/
    }

    public function saveToMedia($file, $is_save = true)
    {
        $name = $file->baseName . '.' . $file->extension;
        if (!$is_save) return $this->getBaseName($file->baseName) . $name;
        $path = $this->getBaseName($file->baseName) . $name;
        $this->awsUpload('storage/' . $path, Yii::getAlias("@storage/{$path}"));
        $model = new $this->uploadsModel;
        $model->filename = $name;
        $model->filesize = $file->size;
        $model->path = $path;
        $model->mimetype = $file->type;
        return $model->save() ? $model->id : null;
    }

    public function uploadWithUrl($url, $name = null)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !file_exists($url)) {
            return false;
        }

        $options = self::extendOptions(['url' => $url]);
        $baseName = !empty($name) ? $name : Yii::$app->security->generateRandomString();
        $path = $this->uploadsAlias . '/' . $this->getBaseName($baseName);
        $name = $baseName . '.' . $options['extension'];
        $name .= (!in_array($options['extension'], ['png', 'jpg', 'jpeg', 'gif'])) ? 'png' : '';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if (file_exists($options['url'])) {
            copy($options['url'], $path . $name);
        }

        return $this->getBaseName($baseName) . $name;
    }

    public function uploadWithMapUrl($url, $name = null)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $options = self::extendOptions(['url' => $url]);
        $baseName = !empty($name) ? $name : Yii::$app->security->generateRandomString();
        $path = $this->uploadsAlias . '/' . $this->getBaseName($baseName);
        $name = $baseName . '.' . $options['extension'];
        $name .= (!in_array($options['extension'], ['png', 'jpg', 'jpeg', 'gif'])) ? 'png' : '';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        copy($options['url'], $path . $name);
        return $this->getBaseName($baseName) . $name;
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
        return chunk_split(substr(preg_replace('/[^A-Za-z0-9]/', '', $baseName), 0, 8), 1, '/');
    }

    public function createBasePath($path)
    {
        return chunk_split(substr(preg_replace('/[^A-Za-z0-9]/', '', $path), 0, 8), 1, '/');
    }
}


?>
