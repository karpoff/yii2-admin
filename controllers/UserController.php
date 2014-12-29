<?php

namespace yii\admin\controllers;

use yii;

class UserController extends yii\admin\components\AdminController
{
	public function behaviors()
	{
		return [
			'access' => [
				'class' => yii\admin\components\AdminAccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'actions' => ['login'],
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'actions' => ['logout', 'language'],
						'roles' => ['@'],
					],
				],
			],
		];
	}

	public function actionLogin() {
		$this->layout = false;

		$model = new yii\admin\models\LoginForm();
		if ($model->load(Yii::$app->request->post())) {
			if (Yii::$app->request->get('validate')) {
				Yii::$app->response->format = 'json';
				return yii\widgets\ActiveForm::validate($model);
			}

			if ($model->login())
				return Yii::$app->getResponse()->redirect($this->module->user->getReturnUrl(yii\helpers\Url::toRoute('/' . $this->module->id)));
		}

		return $this->render('login', [
			'model' => $model,
		]);
	}

	public function actionLogout() {
		$this->module->user->logout();
		$this->redirect(yii\helpers\Url::to('/', true));
	}

	public function actionLanguage($code)
	{
		if (!$this->module->lang || !$this->module->setLanguage($code))
			throw new yii\web\HttpException(404);
	}
}