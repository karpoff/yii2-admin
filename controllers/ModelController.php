<?php

namespace yii\admin\controllers;

use yii;
use yii\admin\behaviors\TranslateBehavior;
use yii\admin\widgets\ModelForm;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\di\Instance;
use yii\helpers\ArrayHelper;

class ModelController extends yii\admin\components\AdminController
{
	public $defaultAction = 'list';

	public $model_class;

	/* @var $model \yii\db\ActiveRecord */
	protected $model;

	protected $scenarios = false;

	public $fields;
	public $tabs;

	public $jsModelFormOptions;

	public $child = false;
	protected $editPopup = false;

	public $attributes;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if (!empty($this->model)) {
			$this->model_class = $this->model;
		}

		if (Yii::$app->getRequest()->get('popup'))
			yii\admin\YiiAdminModule::getInstance()->noBreadcrumbs = true;
		$this->model = Yii::createObject($this->model_class);

		Instance::ensure($this->model, Model::className());

		if ($this->attributes) {
			foreach ($this->attributes as $name => $value) {
				if ($this->model->hasAttribute($name))
					$this->model->setAttribute($name, $value);
			}
		}

		if (Yii::$app->getRequest()->get('popup') || $this->attributes) {
			$this->editPopup = true;
		}

		return true;
	}

	public $processEditFields = true;
	public function editFields() {
		$fields = $this->model->attributes();
		foreach ($this->model->getBehaviors() as $beh) {
			if ($beh instanceof TranslateBehavior) {
				if (!in_array($beh->attribute, $fields))
					$fields[] = $beh->attribute;
			}
		}

		return $this->hideFields($fields);
	}

	public function listFields() {
		return $this->model->attributes();
	}

	public function hideFields($fields) {
		$model = $this->model;
		$unset_fields = $model::primaryKey();

		$out = [];
		foreach ($fields as $name) {
			if (array_search($name, $unset_fields) === false)
				$out[] = $name;
		}
		return $out;
	}

	public function editTabs() { return null; }

	public function processEditFields($fields) {

		$out = [];

		$formats = $this->editFieldFormats();

		foreach ($fields as $name => $field) {
			if (is_numeric($name)) {
				$config = $this->editFieldDefaultConfig($field, isset($formats[$field]) ? $formats[$field] : null);

				if ($config)
					$out[$field] = $config;
				else
					$out[] = $field;
			} else {
				$out[$name] = $this->editFieldDefaultConfig($name, $field);
			}
		}
		return $out;
	}

	/** gets title for selected model entity
	 * @param \yii\db\ActiveRecord $model
	 * @return string title for selected model entity
	 */
	public static function modelTitle($model) {
		$rc = new \ReflectionClass($model->className());
		if ($rc->hasMethod('__toString'))
			return (string) $model;
		return $model->primaryKey;
	}

	public function editFieldDefaultConfig($attribute, $value=null, $options = []) {
		$model = empty($options['model']) ? $this->model : $options['model'];

		if ($value) {
			if (isset($value[0])) {
				$value = ['class' => 'ActiveField', 'config' => $value];
			}

			return $value;
		}

		foreach ($model->getBehaviors() as $beh) {
			if (isset($beh->attribute) && $beh->attribute == $attribute) {
				if ($beh instanceof TranslateBehavior) {
					$config = ['class' => 'Translation'];
					return $config;
				}
			}
		}

		// check if field like xxx_id has relation xxx
		if (strlen($attribute) > 3 && substr($attribute, strlen($attribute) - 3) === '_id') {
			$rel = $this->model->getRelation(substr($attribute, 0, strlen($attribute) - 3), false);
			if ($rel) {
				return ['class' => 'Relation', 'attribute' => substr($attribute, 0, strlen($attribute) - 3)];
			}
		}

		if ($this->model->getRelation($attribute, false))
			return ['class' => 'Relation'];

		return null;
	}

	public function editFieldFormats() { return []; }

	public function listActions()
	{
		$actions = [
			'edit' => [
				'icon' => 'edit',
				'label' => 'Edit',
				'url' => $this->url('edit', ['id' => '__primary_key__'])
			],
			'delete' => [
				'icon' => 'remove',
				'label' => 'Delete',
				'url' => $this->url('delete', ['id' => '__primary_key__']),
				'popup' => true
			],
		];

		if ($this->attributes) {
			$link_params = [
				'id' => Yii::$app->request->get('id'),
				'name' => explode('-', $this->id)[1],
				'relation_params[id]' => '__primary_key__',
			];
			$actions['edit']['url'] = $this->url('/' . Yii::$app->controller->id . '/relation',
				yii\helpers\ArrayHelper::merge($link_params, ['action' => 'edit'])
			);
			$actions['delete']['url'] = $this->url('/' . Yii::$app->controller->id . '/relation',
				yii\helpers\ArrayHelper::merge($link_params, ['action' => 'delete'])
			);
		}

		if ($this->editPopup) {
			$actions['edit']['popup'] = true;
		}
		return $actions;
	}

	public function editActions()
	{
		if (Yii::$app->getRequest()->get('popup'))
			return [];
		$actions = $this->listActions();
		unset($actions['edit']);
		return $actions;
	}

	public function actionList()
	{
		$model = $this->model;
		$query = $model::find();

		if ($this->attributes)
			$query->where($this->attributes);

		$dataProvider = new ActiveDataProvider(['query' => $query]);
		$dataProvider->setSort(false);

		$data = [];
		$data['grid_config'] = ['dataProvider' => $dataProvider];

		$data['grid_config']['id'] = $this->id . '-list';
		$data['grid_config']['options']['class'] = 'grid-view';
		$data['grid_config']['columns'] = $this->listFields();
		$data['grid_config']['columns'][] = [
			'class' => 'yii\admin\widgets\GridActionColumn',
			'actions' => $this->listActions()
		];

		$addOptions = [
			'class' => 'btn btn-primary', 'style' => 'float: right;'
		];
		if ($this->editPopup) {
			$addOptions['data-popup'] = true;
			$addOptions['data-list'] = $data['grid_config']['id'];
		}
		// means that it is relation of some model
		if ($this->attributes) {
			$link_params = [
				'id' => Yii::$app->request->get('id'),
				'name' => explode('-', $this->id)[1],
			];
			$data['grid_config']['options']['data-url'] = $this->url('/' . Yii::$app->controller->id . '/relation', $link_params);
			$addOptions['href'] =  $this->url('/' . Yii::$app->controller->id . '/relation', ArrayHelper::merge($link_params, ['action' => 'add']));
		} else {
			$addOptions['href'] =  $this->url('add');
		}

		$data['add'] = \yii\bootstrap\Button::widget([
			'options' => $addOptions,
			'tagName' => 'a',
			'label' => 'Add'
		]);


		return $this->render('/model/list', $data);
	}

	public function actionValidate($id=null)
	{
		$model = $id ? $this->model->findOne($id) : $this->model;

		if ($this->scenarios)
			$model->scenario = $model->isNewRecord ? 'insert' : 'update';

		$model->load(Yii::$app->request->post());
		Yii::$app->response->format = 'json';
		return ModelForm::validate($model);
	}

	public function actionAdd()	{
		return $this->actionEdit(null);
	}
	public function actionEdit($id)
	{
		if ($id)
			$this->model = $this->model->findOne($id);

		if ($this->scenarios)
			$this->model->scenario = $this->model->isNewRecord ? 'insert' : 'update';

		if ($this->model->load(Yii::$app->request->post())) {
			if ($this->model->save()) {
				if ($this->editPopup) {
					$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
					return $this->model->primaryKey;
				}

				return $this->redirect($this->url('list'));
			}
		}

		$config = $this->jsModelFormOptions ? $this->jsModelFormOptions : [];

		$config['model'] = $this->model;

		if ($this->attributes) {
			$link_params = [
				'id' => Yii::$app->request->get('id'),
				'name' => explode('-', $this->id)[1],
				'action' => 'validate'
			];

			if (!$this->model->isNewRecord)
				$link_params['relation_params']['id'] = $this->model->id;
			$config['validationUrl'] = $this->url('/' . Yii::$app->controller->id . '/relation', $link_params);
		} else {
			$config['validationUrl'] = $this->url('validate', $this->model->isNewRecord ? [] : ['id' => $this->model->getPrimaryKey()]);
		}
		$config['defaultClassPath'] = 'yii\admin\fields';
		$config['fieldClass'] = 'yii\admin\fields\ActiveField';
		if ($this->editPopup) {
			$config['modal'] = true;
			$config['showSubmitButton'] = false;
		}

		if (!isset($config['onSuccess'])) {
			$config['onSuccess'] = Yii::$app->getRequest()->get('popup')
				? '$.yiiAdmin("popupForm", false);'
				: '$.yiiAdmin("loadPage", "'.$this->url('list').'");';
		}

		$config = yii\helpers\ArrayHelper::merge($config, $this->getFormFields());

		$actions = $this->editActions();
		if ($actions) {
			foreach ($actions as &$action) {
				$action['url'] = str_replace('__primary_key__', $this->model->primaryKey, $action['url']);
			}
			$config['actions'] = $actions;
		}

		$title = $id ? static::modelTitle($this->model) : 'Add';
		return $this->render('/model/form', ['config' => $config, 'title' => $title]);
	}

	protected function getFormFields()
	{
		$tabs = $this->editTabs();

		if ($tabs) {
			if ($this->processEditFields) {
				foreach ($tabs as $name => $fields) {
					if (is_string($fields)) {
						$fields = explode(',', $fields);
						foreach ($fields as $ind => $field)
							$fields[$ind] = trim($field);
					}

					$tabs[$name] = $this->processEditFields($fields);
				}
			}
			return ['tabs' => $tabs];
		} else {
			$fields = $this->editFields();
			if ($this->processEditFields)
				$fields = $this->processEditFields($fields);
			return ['fields' => $fields];
		}
	}
	public function actionRelation($id, $name, $action='')
	{
		$this->model = $this->model->findOne($id);
		$fields = $this->getFormFields();

		if (isset($fields['tabs'])) {
			foreach ($fields['tabs'] as $fields) {
				if (isset($fields[$name])) {
					$field = $fields[$name];
					break;
				}
			}
		} else {
			if (isset($fields['fields'][$name]))
				$field = $fields['fields'][$name];
		}

		if (!isset($field))
			throw new \HttpException(404);

		$rel = $this->model->getRelation($name);
		$remote_field = '';
		$own_field = '';
		foreach ($rel->link as $remote_field => $own_field)
			break;

		/** @var \yii\web\Controller $controller */
		$controller = \Yii::createObject(
			isset($field['controller']) ? $field['controller'] : 'yii\admin\controllers\ModelController',
			['relation-' . $name, yii\admin\YiiAdminModule::getInstance(), [
				'model_class' => $rel->modelClass,
				'layout' => 'page',
				'attributes' => [$remote_field => $this->model->getAttribute($own_field)],
			], []]
		);

		return $controller->runAction($action, Yii::$app->request->get('relation_params', []));
	}
	public function returnEdit()
	{
		return $this->model->primaryKey;
	}

	public function actionDelete($id)
	{
		$this->model->findOne($id)->delete();

		if ($this->editPopup)
			return '<script>$.yiiAdmin("popupForm", false);</script>';
		else
			return $this->redirect($this->url('list'));
	}
}
