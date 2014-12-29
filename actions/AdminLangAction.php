<?php

namespace yii\admin\actions;

use Yii;
use dosamigos\switchinput\SwitchBox;

class AdminLangAction extends ViewerAction
{
	/* @var $model \yii\admin\models\Lang */
	protected $model = 'yii\admin\models\Lang';

	public function editFields() {
		return ['name', 'code', 'sort',	'enabled' => ['checkbox'],	'admin' => ['checkbox']];
	}
}