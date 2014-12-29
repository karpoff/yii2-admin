<?php

namespace yii\admin\actions;

use yii;
use yii\admin\behaviors\TranslateBehavior;
use yii\admin\widgets\ModelForm;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\di\Instance;

class ViewerAction extends AdminAction
{
	public $model_class;

	/* @var $model \yii\db\ActiveRecord */
	protected $model;

	protected $default_get_action = 'list';

	protected $scenarios = false;

	public $fields;
	public $tabs;

	public $jsModelFormOptions;

	public $child = false;

	public function init()
	{
		if (!empty($this->model)) {
			$this->model_class = $this->model;
		}

		Instance::ensure($this->model_class, Model::className());

		$id = Yii::$app->request->get('id');
		/* @var $model \yii\db\ActiveRecord */
		$model = $this->model_class;
		$model = $this->child || empty($id) ? Yii::createObject($model) : $model::findOne($id);
		$this->model = $model;
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

	protected function getAction() { return 'list';	}
	public function listActions()
	{
		return [
			'edit' => [
				'label' => 'edit',
				'url' => $this->controller->url(['action' => 'edit', ['id' => '{primary_key}']], true)
			],
			'delete' => [
				'label' => 'delete',
				'url' => $this->controller->url(['action' => 'delete', ['id' => '{primary_key}']], true),
				'confirm' => 'Delete?'
			],
		];
	}

	public function editActions()
	{
		$actions = $this->listActions();
		unset($actions['edit']);
		return $actions;
	}

	public function actionList()
	{
		$model = $this->model;
		$query = $model::find();

		$dataProvider = new ActiveDataProvider(['query' => $query]);
		$dataProvider->setSort(false);

		$data = [];
		$data['add'] = $this->controller->url(['action' => 'add'], true);

		$data['grid_config'] = ['dataProvider' => $dataProvider];

		$data['grid_config']['columns'] = $this->listFields();
		$data['grid_config']['columns'][] = [
			'class' => 'yii\admin\widgets\GridActionColumn',
			'actions' => $this->listActions()
		];

		return $this->controller->render('list', $data);
	}

	public function actionValidate()
	{
		$this->model->load(Yii::$app->request->post());
		Yii::$app->response->format = 'json';
		return ModelForm::validate($this->model);
	}

	public function actionAdd()	{ return $this->actionEdit(); }
	public function actionEdit()
	{
		if ($this->scenarios)
			$this->model->scenario = $this->model->isNewRecord ? 'insert' : 'update';

		if ($this->model->load(Yii::$app->request->post())) {
			$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
			$this->model->save();
			return $this->returnEdit();
		}

		$config = $this->jsModelFormOptions ? $this->jsModelFormOptions : [];

		$config['model'] = $this->model;
		$config['options']['class'] = 'container';
		$config['return_url'] = $this->controller->url(['action' => 'list'], true);
		$config['validationUrl'] = $this->controller->url(['action' => 'validate', $this->model->isNewRecord ? [] : ['id' => $this->model->getPrimaryKey()]]);
		$config['defaultClassPath'] = 'yii\admin\fields';
		$config['fieldClass'] = 'yii\admin\fields\ActiveField';

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
			$config['tabs'] = $tabs;
		} else {
			$fields = $this->editFields();
			if ($this->processEditFields)
				$fields = $this->processEditFields($fields);
			$config['fields'] = $fields;
		}

		$actions = $this->editActions();
		if ($actions) {
			foreach ($actions as &$action) {
				$action['url'] = str_replace('{primary_key}', $this->model->primaryKey, $action['url']);
			}
			$config['actions'] = $actions;
		}

		return $this->controller->render('form', ['config' => $config]);
	}

	public function returnEdit()
	{
		return $this->model->primaryKey;
	}

	public function actionDelete()
	{
		$this->model->delete();
		return $this->controller->redirect($this->controller->url(['action' => 'list'], true));
	}
}
