<?php
namespace yii\admin\actions;

use Yii;
use yii\base\Action;
use yii\web\HttpException;

abstract class AdminAction extends Action
{
    public $params;

	/* @var $controller \yii\admin\controllers\GetController */
	public $controller;

	public final function run()
	{
		if (Yii::$app->request->get('_action_'))
			$action = Yii::$app->request->get('_action_');
		else
			$action = $this->getAction();

		$action = 'action' . $action;
		if (method_exists($this, $action)) {
			return $this->$action();
		}
		throw new HttpException(404);
	}

	protected function getAction()
	{
		return (Yii::$app->request->isPost) ? 'post' : 'get';
	}
}
