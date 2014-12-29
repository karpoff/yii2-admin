<?php
namespace yii\admin\fields;

use yii\base\ErrorHandler;

class ActiveField extends \yii\widgets\ActiveField
{
	/* @var $model \yii\db\ActiveRecord */
	public $model;

	public $attribute;

	public $config;

	public $template = "{label}\n{input}\n{hint}\n{error}";

	public function save(){}

	public function __toString() {
		if ($this->config) {
			try {
				$method = $this->config[0];
				array_shift($this->config);
				call_user_func_array([$this, $method], $this->config);
			} catch (\Exception $e) {
				ErrorHandler::convertExceptionToError($e);
				return '';
			}
		}
		return parent::__toString();
	}
}
