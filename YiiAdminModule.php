<?php

namespace yii\admin;

use Yii;
use yii\admin\models\MenuItem;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\widgets\Breadcrumbs;

class YiiAdminModule extends \yii\base\Module
{
    public $controllerNamespace = 'yii\admin\controllers';
	public $layout = 'main';

	public $index_redirect;

	public $lang;
	public $noBreadcrumbs = false;

	public $config_file;

	/** @var \yii\admin\models\LangInterface $_language */
	protected $_language;
	/** @var \yii\db\ActiveRecord $_languages */
	protected $_languages;

	/** @var \yii\admin\models\MenuItem $_menuItem */
	protected $_menuItem;

	public function __construct($id, $parent = null, $config = [])
	{
		$_config = [];
		if (isset($config['config_file'])) {
			$_config = require(Yii::getAlias($config['config_file']));
		}
		$config = ArrayHelper::merge($_config, $config);

		$config['components']['user']['class'] = 'yii\admin\components\user\User';

		parent::__construct($id, $parent, $config);
	}

	public function init()
	{
		/*$handler = new \yii\web\ErrorHandler(['errorAction' => $this->id . '/default/error']);
		Yii::$app->set('errorHandler', $handler);
		$handler->register();*/

		Yii::$app->getI18n()->translations['yii.admin'] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'sourceLanguage' => 'en-US',
			'basePath' => __DIR__ . DIRECTORY_SEPARATOR . 'messages',
        ];

		$handler = new ErrorHandler();
		$handler->errorView = '@yii/views/errorHandler/exception.php';
		\Yii::$app->set('errorHandler', $handler);
		$handler->register();
	}

	public function createController($route)
	{
		if ($this->lang) {
			if ($this->lang === true) {
				$this->lang = 'yii\admin\models\Lang';
			}

			Yii::$app->params['yii.admin.language'] = true;

			$languages = $this->getLanguages('admin');

			if (!empty($languages)) {
				/* @var \yii\admin\models\LangInterface $language */

				$lang_id = \Yii::$app->session->get('admin_language_id');
				if ($lang_id) {
					if (isset($languages[$lang_id])) {
						$this->_language = $languages[$lang_id];
					}
				}


				if (!$this->_language) {
					$code = explode('-', Yii::$app->language)[0];

					foreach ($languages as $language) {
						if ($language->getCode() == $code) {
							$this->_language = $language;
							\Yii::$app->session->set('admin_language_id', $language->getId());
							break;
						}
					}
				}

				if (!$this->_language) {
					$language = array_values($languages)[0];
					$this->_language = $language;
					\Yii::$app->session->set('admin_language_id', $language->getId());
				}

				if ($this->_language) {
					Yii::$app->language = $this->_language->getCode();
				}
			}
		}

		$path = explode('/', $route);
		$controller_id = $path[0];
		$action = isset($path[1]) ? $path[1] : '';

		if (empty($controller_id)) {
			if ($this->index_redirect) {
				Yii::$app->getResponse()->redirect(Url::to($this->id . '/' .$this->index_redirect, true));
			}
			if ($this->defaultRoute != 'default') {
				$controller_id = $this->defaultRoute;
			} else {
				return parent::createController($route);
			}
		}

		if (substr($controller_id, 0, 6) == 'admin-') {
			$path = substr($controller_id, 6);
			$system = [
				'menu' => 'yii\admin\controllers\AdminMenuController',
				'lang' => 'yii\admin\controllers\AdminLangController',
				//'users' => [],
				'user' => 'yii\admin\controllers\UserController',
			];
			if (!isset($system[$path]))
				return false;

			$controller = Yii::createObject($system[$path], [$controller_id, $this]);
			return [$controller, $action];
		}

		$item = MenuItem::findByPath($controller_id);

		if (!$item) {
			throw new HttpException(404);
		}

		$type = $item->getAttribute('type');

		if ($type == MenuItem::TYPE_CATEGORY) {
			$controller = Yii::createObject('yii\admin\controllers\AdminMenuListController', [$controller_id, $this, ['item' => $item], []]);
		} else if (!$item->getAttribute('class')) {
			//throw new HttpException(404);
		} else if ($type == MenuItem::TYPE_CONTROLLER) {
			$controller = Yii::createObject($item->getAttribute('class'), [$controller_id, $this]);
		} else if ($type == MenuItem::TYPE_MODEL) {
			$controller = Yii::createObject('yii\admin\controllers\ModelController', [$controller_id, $this, ['model_class' => $item->getAttribute('class')], []]);
		}

		$this->_menuItem = $item;

		return empty($controller) ? false : [$controller, $action];
	}

	public function getMenu()
	{
		$items = MenuItem::getMenu();
		$tree = [];
		$direct_link = [0 => ['items' => &$tree]];

		$url = '/' . $this->id . '/';

		$changed = true;
		while ($changed) {
			$changed = false;
			$next = [];
			$added = [];
			/** @var \yii\admin\models\MenuItem $item */
			foreach ($items as $item) {
				$id = intval($item->getAttribute('id'));
				$parent = intval($item->getAttribute('parent'));
				if (isset($direct_link[$parent]) && empty($added[$parent])) {
					$direct_link[$id] = [
						'label' => $item->title(),
						'url' => $url . $item->getAttribute('full_path'),
					];

					$direct_link[$parent]['items'][] = &$direct_link[$id];
					$added[$id] = true;
					$changed = true;
				} else {
					$next[] = $item;
				}
			}
			$items = $next;
		}
		return $tree;
	}


	public function getLanguage() {
		return $this->_language;
	}

	public function setLanguage($code)
	{
		/** @var \yii\admin\models\LangInterface $language */
		foreach ($this->getLanguages('admin') as $language) {
			if ($language->getCode() == $code) {
				$this->_language = $language;
				\Yii::$app->session->set('admin_language_id', $language->getId());
				return true;
			}
		}

		return false;
	}

	public function getLanguages($filter = 'active')
	{
		if (!$this->lang)
			return [];

		if ($this->_languages === null) {
			/** @var \yii\admin\models\Lang $lang_model */
			$lang_model = $this->lang;

			$this->_languages = ArrayHelper::index($lang_model::getAll(), 'id');
		}

		if (empty($this->_languages))
			return [];

		$filter_func = null;
		if ($filter == 'active') {
			$filter_func = function($var) { return $var->isEnabled(); };
		} else if ($filter == 'admin') {
			$filter_func = function($var) { return $var->isAdmin(); };
		}

		return $filter_func ? array_filter($this->_languages, $filter_func) : $this->_languages;
	}

	public function getLanguageId()
	{
		$language = $this->getLanguage();
		if ($language)
			return $language->getId();

		$languages = $this->getLanguages();
		if (!empty($languages))
			return array_values($languages)[0]->getId();

		return null;
	}

	public function getBreadcrumbs($title) {
		if (!$this->_menuItem || $this->noBreadcrumbs)
			return null;
		$links = [];
		$links[] = [
			'label' => $this->_menuItem->title(),
			'url' => '/' . $this->id . '/' . $this->_menuItem->getAttribute('full_path'),
		];
		$item = $this->_menuItem;
		while ($item && $item->getAttribute('parent')) {
			$item = MenuItem::findOne($item->getAttribute('parent'));
			array_unshift($links, [
				'label' => $item->title(),
				'url' => '/' . $this->id . '/' . $item->getAttribute('full_path'),
			]);
		}

		if ($links[sizeof($links) - 1]['label'] !== $title) {
			$links[] = ['label' => $title];
		}
		return Breadcrumbs::widget([
			'homeLink' => [
				'label' => Yii::t('yii', 'Home'),
				'url' => '/' . $this->id,
			],
			'links' => $links
		]);
	}

	public function getMenuItemTitle() {
		return $this->_menuItem ? $this->_menuItem->title() : Yii::t('yii', 'Home');
	}
}
