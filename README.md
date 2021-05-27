Thumbnail Image Helper for Yii2
========================

Yii2 helper for creating and caching thumbnails on real time.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require --prefer-dist alkurn/yii2-upload "dev-master"
```
or add

```json
"alkurn/yii2-upload" : "*"
```

to the require section of your application's `composer.json` file.

* Add a new component in `components` section of your application's configuration file (optional), for example:

```php
'components' => [ 
    'upload' => [
                'class' =>'alkurn\upload\Upload',
                'uploadsAlias' => Yii::getAlias('@storage/'),
                'uploadsModel' => CoreMedia::class,
            ],
]
```

and in `bootstrap` section, for example:

```php
'bootstrap' => ['log', 'thumbnail', 'upload'],
```

It is necessary if you want to set global helper's settings for the application.

Usage
-----
For example:

```php
use alkurn\upload\Upload;

$image = Yii::$app->upload->upload('image', $model);
``` 
