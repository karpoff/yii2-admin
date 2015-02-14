<?php
namespace yii\admin\models;

use yii\admin\behaviors\TranslateBehavior;
use yii\admin\YiiAdminModule;
use yii\base\Controller;
use yii\db\ActiveRecord;
use yii\db\Expression;

use \yii\base\Action;
use \yii\base\Model;
use yii\di\Instance;

class MenuItem extends ActiveRecord {

	const TYPE_CATEGORY = 0;
	const TYPE_CONTROLLER = 1;
	const TYPE_MODEL = 2;

	use TranslatedModelTrait;

	public static function tableName() {
		return 'yii_admin_menu';
	}

	public function rules() {
		$rules = [
			[['title', 'path'], 'trim'],
			[['path'], 'required'],
			[['type', 'class'], 'default'],
			[['hidden'], 'boolean'],
			['class', 'validate_class'],
			['path', 'unique'],
			['path', 'compare', 'compareValue' => 'admin', 'operator' => '!='],
			['path', 'compare', 'compareValue' => 'user', 'operator' => '!='],
		];

		if (!YiiAdminModule::getInstance()->lang)
			$rules[] = [['title'], 'required'];
		return $rules;
	}

	function validate_class($attribute, $param) {
		if (empty($this->class))
			return;

		$types = [
			self::TYPE_CONTROLLER => [Controller::className(), ['check', \Yii::$app->controller->module]],
			self::TYPE_MODEL => Model::className(),
		];

		$error = true;
		foreach ($types as $type => $class) {
			try {
				if (is_array($class)) {
					$check_type = $class[0];
					$params = $class[1];
				} else {
					$check_type = $class;
					$params = [];
				}
				$obj = \Yii::createObject($this->class, $params);
				Instance::ensure($obj, $check_type);

				$this->setAttribute('type', $type);
				$error = false;
				break;
			} catch (\Exception $e) {}
		}

		if ($error) {
			$this->addError($attribute, 'class not supported for menu');
		}
	}

	public static function find($q = null)
	{
		return parent::find($q)->orderBy('sort ASC');
	}

	public static function findByPath($path)
	{
		return self::find()->where(['full_path' => $path])->one();
	}

	public function getChildren()
	{
		return self::find()
			->where($this->getIsNewRecord() ? 'parent IS NULL' : ['parent' => $this->getAttribute('id')])
			->select('id, title, full_path')
			->orderBy('sort ASC')
			->all();
	}

	public function title() {
		return YiiAdminModule::getInstance()->lang ? $this->trans->title : $this->title;
	}

	public function insert($runValidation = true, $attributes = null)
	{
		$row = self::find()->select('sort')->orderBy('sort DESC')->where('parent ' . (empty($this->parent) ? 'IS NULL' : '= ' . $this->parent))->one();
		$this->sort = $row ? $row->sort+1 : 0;
		parent::insert($runValidation, $attributes);
	}

	public function save($runValidation = true, $attributeNames = null)
	{
		$parent = $this->parent;
		$this->full_path = $this->path;

		while ($parent) {
			$row = self::findOne($parent);
			if (!$row) break;
			$this->full_path = $row->path . '-' . $this->full_path;
			$parent = $row->parent;
		}

		return parent::save($runValidation, $attributeNames);
	}

	public function delete()
	{
		/* @var MenuItem $child */
		foreach (self::find()->where(['parent' => $this->id])->all() as $child) {
			$child->delete();
		}

		$where = 'parent ' . (empty($this->parent) ? 'IS NULL' : '= ' . $this->parent). ' AND sort > ' . $this->sort;
		MenuItem::updateAll(['sort' => new Expression('sort-1')], $where);

		parent::delete();
	}

	public function isItem()
	{
		return $this->type != self::TYPE_CATEGORY;
	}

	public function sort($sort)
	{
		$where = 'parent ' . (empty($this->parent) ? 'IS NULL' : '= ' . $this->parent). ' AND ';
		$current_sort = $this->getAttribute('sort');
		if ($sort < $current_sort) {
			$set = new Expression('sort+1');
			$where .= "sort >= {$sort} AND sort < {$current_sort}";
		} else if ($sort > $current_sort) {
			$set = new Expression('sort-1');
			$where .= "sort > {$current_sort} AND sort <= {$sort}";
		} else {
			return;
		}
		$this->setAttribute('sort', $sort);

		if ($this->update())
			MenuItem::updateAll(['sort' => $set], $where);
	}

	function behaviors()
	{
		$behaviors = [];
		if (isset(\Yii::$app->params['yii.admin.language'])) {
			$behaviors[] = [
				'class' => TranslateBehavior::className(),
				'attribute' => 'title',
				'scenarios' => ['default'],
				'model' => MenuItemText::className(),
				'copyDefault' => ['title']
			];
		}
		return $behaviors;
	}
}