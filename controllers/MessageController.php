<?php

namespace yii\admin\controllers;

use Yii;
use yii\admin\models\Message;
use yii\admin\models\MessageSource;
use yii\admin\YiiAdminModule;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\admin\widgets\MessageWidget;;

class MessageController extends \yii\admin\components\AdminController
{
	public $category = 'app';
	public $languages = null;

	protected $editSource;
	public function init() {
		parent::init();

		if ($this->languages === null) {
			foreach (YiiAdminModule::getInstance()->getLanguages() as $lang) {
				if ($lang !== Yii::$app->sourceLanguage)
					$this->languages[] = $lang->getAttribute('code');
			}
		}

		$this->editSource = YiiAdminModule::getInstance()->user->isAdmin;
	}
	public function actionIndex() {

		if (Yii::$app->request->isPost) {
			$this->save();
		}
		$sources = [];
		foreach (MessageSource::find()->where(['category' => $this->category])->all() as $source) {
			$sources[$source->getAttribute('id')] = [
				'id' => $source->getAttribute('id'),
				'message' => $source->getAttribute('message'),
				'translations' => [],
			];
		}

		foreach (Message::find()->joinWith('source')->where(['category' => $this->category])->all() as $message) {
			if (isset($sources[$message->getAttribute('id')]))
				$sources[$message->getAttribute('id')]['translations'][$message->getAttribute('language')] = $message->getAttribute('translation');
		}

		$config = [
			'sources' => $sources,
			'edit_source' => $this->editSource,
			'languages' => $this->languages,
		];

		 if (Yii::$app->request->isAjax)
			 return Json::encode($config);

		return $this->renderContent(MessageWidget::widget($config));
	}

	protected function save() {

		$new_sources = Yii::$app->request->post('sources');
		/** @var MessageSource[] $sources */
		$sources = ArrayHelper::index(MessageSource::find()->where(['category' => $this->category])->all(), function($el) { return 'id'.$el->id;});

		if ($this->editSource) {
			foreach ($new_sources as $id => $new_source) {
				if (isset($new_source['deleted']) || array_search(trim($new_source['message']), ['', '-']) !== false) {
					if (isset($sources[$id])) {
						$sources[$id]->delete();
						unset($sources[$id]);
					}
					continue;
				}

				if (!isset($sources[$id])) {
					$sources[$id] = new MessageSource();
					$sources[$id]->setAttribute('category', $this->category);
				}

				$sources[$id]->setAttribute('message', $new_source['message']);
				$sources[$id]->save();
			}
		}

		foreach (Message::find()->joinWith('source')->where(['category' => $this->category])->all() as $message) {
			if (isset($new_sources['id'.$message->getAttribute('id')]['translations'][$message->getAttribute('language')])) {
				$message->setAttribute('translation', $new_sources['id'.$message->getAttribute('id')]['translations'][$message->getAttribute('language')]);
				$message->save();
				unset ($new_sources['id'.$message->getAttribute('id')]['translations'][$message->getAttribute('language')]);
			} else {
				$message->delete();
			}
		}

		foreach ($new_sources as $source_id => $source) {
			if (!isset($sources[$source_id]) || empty($source['translations']))
				continue;

			foreach ($source['translations'] as $lang => $translation) {
				$message = new Message();
				$message->setAttribute('id', $sources[$source_id]->getAttribute('id'));
				$message->setAttribute('language', $lang);
				$message->setAttribute('translation', $translation);
				$message->save();
			}
		}
	}
}
