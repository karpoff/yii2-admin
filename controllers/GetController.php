<?php

namespace yii\admin\controllers;

use yii;
use yii\web\Controller;
use yii\admin\components\AdminAccessControl;

class GetController extends Controller
{
	protected $page_action;
	protected $current_path;

	public function behaviors()
	{
		return [
			'access' => [
				'class' => AdminAccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'actions' => ['index', 'page', 'language'],
						'roles' => ['@'],
					],
				],
			],
		];
	}

	public function url($data = [], $hash = false) {
		$path = $this->current_path;
		foreach ($data as $key => $value) {
			if (is_array($value))
				$params = $value;
			else if ($key === 'path')
				$path = $value;
			else if ($key === 'action')
				$action = $value;
		}

		if ($hash) {
			$url = $path;
			if (!empty($action))
				$url .= '-' . $action;
			if (!empty($params)) {
				foreach ($params as $key => $value)
					$url .= ';' . $key . '=' . $value;
			}
			return '#' . $url;
		} else {
			$url_data = ['get/page'];
			$url_data['_path_'] = $path;
			if (!empty($action))
				$url_data['_action_'] = $action;

			if (!empty($params)) {
				$url_data += $params;
			}
			return yii\helpers\Url::toRoute($url_data);
		}
	}

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLanguage($code)
    {
		if (!$this->module->lang || !$this->module->setLanguage($code))
			throw new yii\web\HttpException(404);
    }

	public function actions()
	{
		return $this->page_action ? ['page' => $this->page_action] : [];
	}

	protected function getSystemActions()
	{
		return [
			'admin/menu' => [
				'class' => 'yii\admin\actions\AdminMenuAction'
			],
			'admin/lang' => [
				'class' => 'yii\admin\actions\AdminLangAction'
			],
			'admin/users' => [],
			'user/logout' => [],
		];
	}

	protected function parsePageParams()
	{
		$out = [];
		$params_string = ltrim(trim(Yii::$app->request->get('_path_'), '#'));
		$params = explode(';', $params_string);
		$path = explode('-', $params[0]);
		$out['path'] = $path[0];
		if (!empty($path[1]))
			$out['action'] = $path[1];
		array_shift($params);

		return $out;
	}

	public function runAction($id, $params = [])
	{
		if ($id != 'page')
			return parent::runAction($id, $params);

		$this->layout = 'page';

		$this->current_path = Yii::$app->request->get('_path_');
		$system = $this->getSystemActions();

		if (isset($system[$this->current_path])) {
			$this->page_action = $system[$this->current_path];
			return parent::runAction($id);
		}

		$item = yii\admin\models\MenuItem::findByPath($this->current_path);

		if (!$item) {
			throw new yii\web\HttpException(404);
		}

		if ($item->type == yii\admin\models\MenuItem::TYPE_ACTION) {
			$this->page_action = $item->class;
			return parent::runAction($id);
		} else if ($item->type == yii\admin\models\MenuItem::TYPE_MODEL) {
			$this->page_action = ['class' => 'yii\admin\actions\ViewerAction', 'model_class' => $item->class];
			return parent::runAction($id);
		} else if ($item->type == yii\admin\models\MenuItem::TYPE_CATEGORY) {
			$this->page_action = ['class' => 'yii\admin\actions\AdminMenuListAction', 'item' => $item];
			return parent::runAction($id);
		}

		return true;
	}

	public function redirect($url, $statusCode = 302)
	{
		if ($url[0] == '#')
			return "<script>window.location.hash = '{$url}'</script>";

		return parent::redirect($url, $statusCode);
	}
}
