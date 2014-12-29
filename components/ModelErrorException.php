<?php
namespace yii\admin\components;

use Yii;
use yii\web\HttpException;

class ModelErrorException extends HttpException
{
	/**
	 * @param \yii\db\ActiveRecord $model
	 */
	public function __construct($model)
	{
		Yii::$app->response->statusCode = 500;
		Yii::$app->response->content = json_encode($model->getErrors());
		Yii::$app->response->send();
		Yii::$app->end();
	}
}
