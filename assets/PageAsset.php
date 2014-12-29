<?php

namespace yii\admin\assets;

use yii;

class PageAsset extends AdminAsset
{
	public function init() {
		parent::init();

		$this->js = self::$js_static;
		$this->css = self::$css_static;

		function endsWith($haystack, $needle) {
			if (strlen($needle) > strlen($haystack))
				return false;
			// search forward starting from end minus needle length characters
			return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
		}

		foreach (self::$ext_static as $ext) {
			//$qq =
			foreach (@scandir($this->sourcePath.'/ext/'.$ext) as $file) {
				if (endsWith($file, '.js'))
					$this->js[] = 'ext/' . $ext . '/' . $file;
				if (endsWith($file, '.css'))
					$this->css[] = 'ext/' . $ext . '/' . $file;

			}
		}
	}

    public $css = [];
    public $js = [];
    public $depends = [];

	protected static $js_static = [];
	protected static $css_static = [];
	protected static $ext_static = [];

	public static function addJs($path) { self::$js_static[] = 'js/'.$path; }
	public static function addCss($path) { self::$css_static[] = 'css/'.$path; }

	public static function addExt($path) { self::$ext_static[] = $path; }
}
