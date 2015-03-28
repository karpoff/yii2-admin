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
	public $canAddRecord = true;

	public $sortable = false;

	public $relation_filter = false;

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
		if ($this->fields)
			return $this->fields;

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

	public function editTabs() {
		return ($this->tabs) ? $this->tabs : null;
	}

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

	public function getRelationFilter() {
		$filter = is_array($this->relation_filter) ? $this->relation_filter : ['relation' => $this->relation_filter];
		if (!isset($filter['value']))
			$filter['value'] = Yii::$app->request->get('relation_filter');
		return $filter;
	}

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
		if ($this->model->getIsNewRecord())
			unset($actions['delete']);
		return $actions;
	}

	protected function getListQuery()
	{
		$model = $this->model;
		$query = $model::find();

		if ($this->attributes)
			$query->where($this->attributes);

		if ($this->relation_filter) {
			$filter = $this->getRelationFilter();
			$relation = $model->getRelation($filter['relation']);
			$where = [];
			foreach ($relation->link as $field)
				$where = empty($filter['value']) ? new yii\db\Expression($field.' IS NULL') : [$field => $filter['value']];
			$query->andWhere($where);
		}
		if ($this->sortable)
			$query->orderBy('sort asc');

		return $query;
	}

	public function actionList()
	{
		$dataProvider = new ActiveDataProvider(['query' => $this->getListQuery()]);
		$dataProvider->setSort(false);

		$data = [];
		if ($this->sortable)
			$data['sortable_url'] = $this->url('sort');

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

		if ($this->relation_filter) {
			$filter = $this->getRelationFilter();
			$relation = $this->model->getRelation($filter['relation']);
			$remote_field = '';
			foreach ($relation->link as $remote_field => $own_field)
				break;
			/** @var \yii\db\ActiveRecord $model */
			$model = $relation->modelClass;

			$relation_data = ['' => Yii::t('yii', '(not set)')];
			foreach ($model::find()->all() as $id) {
				$relation_data[$id->getAttribute($remote_field)] = (string) $id;
			}
			$params = Yii::$app->request->get();
			unset($params['relation_filter']);
			$params['relation_filter'] = '';

			$data['relation_filter'] = [
				'data' => $relation_data,
				'current' => $filter['value'],
				'url' => $this->url('list', $params),
			];
		}

		if ($this->canAddRecord) {
			$data['add'] = \yii\bootstrap\Button::widget([
				'options' => $addOptions,
				'tagName' => 'a',
				'label' => 'Add'
			]);
		}

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
		if (!$this->canAddRecord) {
			throw new yii\web\HttpException(404);
		}
		return $this->actionEdit(null);
	}
	public function actionEdit($id)
	{
		if ($id)
			$this->model = $this->model->findOne($id);

		if ($this->scenarios)
			$this->model->scenario = $this->model->isNewRecord ? 'insert' : 'update';

		if ($this->model->load(Yii::$app->request->post())) {
			if (!$id && $this->sortable) {
				$model = $this->model;
				$sort = $model->getAttribute('sort');
				if ($sort === null) {
					$max_sort = $model::find()->select('MAX(sort) as max_sort')->asArray()->one();
					if ($max_sort) {
						$max_sort = intval($max_sort['max_sort']) + 10;
					} else {
						$max_sort = 0;
					}
					$model->setAttribute('sort', $max_sort);
				}
			}

			if ($this->model->save()) {
				$this->afterSave();
				if ($this->editPopup) {
					$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
					return $this->model->primaryKey;
				}

				$params = [];
				if ($this->relation_filter) {
					$filter = $this->getRelationFilter();
					$relation = $this->model->getRelation($filter['relation']);
					$own_field = '';
					foreach ($relation->link as $own_field)
						break;
					$params['relation_filter'] = $this->model->getAttribute($own_field);
				}
				return $this->redirect($this->url('list', $params));
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
			$config['validationUrl'] = $this->url('validate', $this->model->getIsNewRecord() ? [] : ['id' => $this->model->getPrimaryKey()]);
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

		$title = $this->model->getIsNewRecord() ? 'Add' : static::modelTitle($this->model);
		return $this->render('/model/form', ['config' => $config, 'title' => $title]);
	}

	public function actionSort() {
		if (!$this->sortable)
			throw new \HttpException(404);

		$order = Yii::$app->request->get('order');
		$records = [];
		$sort = 0;
		foreach ($order as $o) {
			$records[$o] = $sort;
			$sort += 10;
		}

		/** @var \yii\db\ActiveRecord $record */
		foreach ($this->getListQuery()->all() as $record) {
			$pk = $record->getPrimaryKey();
			if (isset($records[$pk])) {
				$record->setAttribute('sort', $records[$pk]);
			} else {
				$record->setAttribute('sort', $sort);
				$sort += 10;
			}
			$record->save();
		}
	}

	public function getFormFields()
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
		$this->afterDelete();

		if ($this->editPopup)
			return '<script>$.yiiAdmin("popupForm", false);</script>';
		else
			return $this->redirect($this->url('list'));
	}

	public function afterSave() {}
	public function afterDelete() {}
}
