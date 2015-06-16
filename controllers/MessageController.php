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

	public function actionLoad() {

		$data = ['mode' => 'load'];
		if (Yii::$app->request->isPost) {
			$this->load();
		}

		return $this->render('/admin/messages', $data);
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
		Yii::$app->getCache()->flush();
	}

	protected function load() {
		$new_messages = [];

		foreach ($this->languages as $lang) {
			$file = Yii::getAlias(Yii::$app->request->post('path').'/'.$lang.'/'.$this->category.'.php', false);

			if (!file_exists($file))
				continue;

			$new_messages[$lang] = require($file);
		}

		if (Yii::$app->request->post('mode') == 'replace') {
			Yii::$app->db->createCommand('TRUNCATE TABLE ' . MessageSource::tableName())->execute();
			Yii::$app->db->createCommand('TRUNCATE TABLE ' . Message::tableName())->execute();
		}

		$current_sources = ArrayHelper::index(MessageSource::find()->where(['category' => $this->category])->all(), 'message');
		$current_messages = [];

		/** @var $message Message */
		foreach (Message::find()->joinWith('source')->where(['category' => $this->category])->all() as $message) {
			$current_messages[$message->source->getAttribute('message')][$message->getAttribute('language')] = $message;
		}

		foreach ($new_messages as $lang => $messages) {
			foreach ($messages as $source_message => $message) {
				if (!is_string($message))
					continue;

				if (!isset($current_sources[$source_message])) {
					$s = new MessageSource();
					$s->setAttribute('category', $this->category);
					$s->setAttribute('message', $source_message);
					$s->save();
					$current_sources[$source_message] = $s;
				}

				if (!isset($current_messages[$source_message][$lang])) {
					$m = new Message();
					$m->setAttribute('language', $lang);
					$current_messages[$source_message][$lang] = $m;
				}

				$current_messages[$source_message][$lang]->setAttribute('id', $current_sources[$source_message]->getAttribute('id'));
				$current_messages[$source_message][$lang]->setAttribute('translation', $message);
				$current_messages[$source_message][$lang]->save();
			}
		}
	}
}
