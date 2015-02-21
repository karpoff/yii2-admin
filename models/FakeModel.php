<?php

namespace yii\admin\models;

use yii;

abstract class FakeModel extends \yii\db\ActiveRecord
{
	/** @var  \yii\admin\models\SettingsTranslationItem[] $_items */
	protected $_items;
	protected $model_attributes = [];
	private $_model_attributes = [];

	public function getIsNewRecord() { return false; }
	public function getPrimaryKey($asArray=false) { return $asArray ? [] : ''; }

	protected abstract function findItems();
	protected abstract function addAttribute($name);

	public function __get($name) {
		return $this->getAttribute($name);
	}
	public function hasAttribute($name) {
		if (!$this->_items) {
			$this->_items = [];
			/** @var \yii\db\ActiveRecord $item */
			foreach ($this->findItems() as $item) {
				$this->_items[$item->getAttribute('name')] = $item;
			}
		}
		return isset($this->_items[$name]);
	}
	public function setAttribute($name, $value) {
		if (array_search($name, $this->model_attributes) !== false) {
			$this->_model_attributes[$name] = $value;
			return;
		}
		if (!$this->hasAttribute($name)) {
			$this->_items[$name] = $this->addAttribute($name);
		}
		$this->_items[$name]->setAttribute('value', $value);
	}
	public function setAttributes($values, $safeOnly = true)
	{
		if (is_array($values)) {
			foreach ($values as $name => $value) {
				$this->setAttribute($name, $value);
			}
		}
	}
	public function getAttribute($name) {
		if (array_search($name, $this->model_attributes) !== false) {
			return $this->_model_attributes[$name];
		}
		if ($this->hasAttribute($name)) {
			return $this->_items[$name]->getAttribute('value');
		}
		return null;
	}
	public function save($runValidation=true, $attributeNames=null) {
		if ($runValidation && !$this->validate($attributeNames)) {
			return false;
		}

		if (!$this->beforeSave(false)) {
			return false;
		}

		foreach ($this->_items as $item) {
			$item->save($runValidation=true, $attributeNames=null);
		}

		$this->afterSave(false, []);

		return true;
	}
	public function attributes()
	{
		$this->hasAttribute('');
		return array_keys($this->_items);
	}

	public function offsetGet($offset) {
		return $this->getAttribute($offset);
	}

	public function offsetSet($offset, $item) {
		$this->setAttribute($offset, $item);
	}

	public function offsetUnset($offset) {
		if ($this->hasAttribute($offset)) {
			unset($this->_items[$offset]);
		}
	}

	public function scenarios()
	{
		return [
			'default' => [],
			'update' => [],
		];
	}
}
