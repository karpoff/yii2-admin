<?php

namespace yii\admin\assets;

use yii;
use yii\web\AssetBundle;

class MessageAsset extends AssetBundle
{
	public $sourcePath = '@vendor/karpoff/yii2-admin/assets';

	public $css = [
	];
	public $js = [
		'js/yiiMessages.js',
		'js/editableTable.js',
	];
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapAsset',
		'yii\jui\JuiAsset',
	];
}
