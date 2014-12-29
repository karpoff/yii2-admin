<?php

namespace yii\admin\controllers;

use yii;

class AdminLangController extends ModelController
{
	/* @var $model \yii\admin\models\Lang */
	protected $model = 'yii\admin\models\Lang';

	public function editFields() {
		return ['name', 'code', 'sort',	'enabled' => ['checkbox'],	'admin' => ['checkbox']];
	}
}