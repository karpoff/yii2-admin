<?php

namespace yii\admin\models;

use yii;

/**
 * This is the model class for table "portfolio_text".
 *
 * @property integer $portfolio_id
 * @property integer $lang_id
 * @property string $title
 */
class MenuItemText extends Translation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii_admin_menu_text';
    }

	protected static function relations() {
		return [MenuItem::className(), 'menu_item_id', 'id'];
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['menu_item_id', 'lang_id', 'title'], 'required'],
            [['menu_item_id', 'lang_id'], 'integer'],
            [['title'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'menu_item_id' => 'Menu Item ID',
            'lang_id' => 'Lang ID',
            'title' => 'Title',
        ];
    }
}
