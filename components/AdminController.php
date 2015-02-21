<?php

namespace yii\admin\components;

use yii;

class AdminController extends yii\web\Controller
{
	/** @var  \yii\admin\YiiAdminModule $module */
	public $module;
	public $renderAjax = true;

	public function behaviors()
	{
		return [
			'access' => [
				'class' => AdminAccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
	}

	public function render($view, $params = [])
	{
		if ($this->layout !== false && Yii::$app->request->getIsAjax())
			$this->layout = 'page';

		$content = parent::render($view, $params);

		if (Yii::$app->request->getIsAjax() && $this->renderAjax) {
			return yii\helpers\Json::encode([
				'content' => $content,
				'title' => $this->getView()->title
			]);
		}
		return $content;
	}

	public function redirect($url, $statusCode = 302)
	{
		if (Yii::$app->request->getIsAjax()) {
			$response = Yii::$app->getResponse();
			$response->isSent = false;
			$response->data = yii\helpers\Json::encode([
				'url' => $url
			]);
			Yii::$app->end(0, $response);
		}

		return parent::redirect($url, $statusCode);
	}

	public function url($route, $params = [])
	{
		$url = '/' . $this->module->id;
		//relative path
		if (empty($route) || $route[0] !== '/') {
			$url .= '/' . $this->id;
			if ($route == $this->defaultAction) {
				$route = '';
			} else {
				$url .= '/';
			}
		}

		$url .= $route;

		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}
		return $url;
	}
}