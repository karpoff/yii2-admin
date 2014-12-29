<?php

namespace yii\admin\assets;

use yii;
use yii\web\AssetBundle;
use yii\web\View;

class AdminAsset extends AssetBundle
{
	public $sourcePath = '@vendor/karpoff/yii2-admin/assets';

	public $jsOptions = ['position' => View::POS_HEAD];
    public $css = [
        'css/admin.css',
        'css/flags.css',
    ];
    public $js = [
		'js/yiiAdmin.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
