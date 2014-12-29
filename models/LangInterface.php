<?php

namespace yii\admin\models;

interface LangInterface
{
	public function isEnabled();
	public function isAdmin();
	public function getId();
	public function getCode();
	public function getTitle();
	public static function getAll();
	public static function getCurrentId();
}