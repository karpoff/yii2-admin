<?php

namespace yii\admin\controllers;

use yii;
use yii\web\Controller;

class DefaultController extends yii\admin\components\AdminController
{
	public function actionIndex()
	{
		return $this->render('index');
	}

	public function actionError()
	{
		return 'ee';
	}
}