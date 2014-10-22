<?php

namespace biz\core\inventory\models;

use Yii;

/**
 * This is the model class for table "{{%good_movement_dtl}}".
 *
 * @property integer $movement_id
 * @property integer $warehouse_id
 * @property integer $product_id
 * @property double $qty
 * @property double $item_value
 * @property double $trans_value
 *
 * @property GoodMovement $movement
 */
class GoodMovementDtl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%good_movement_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['movement_id', 'warehouse_id', 'product_id', 'qty'], 'required'],
            [['movement_id', 'warehouse_id', 'product_id'], 'integer'],
            [['qty', 'item_value', 'trans_value'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'movement_id' => 'Movement ID',
            'warehouse_id' => 'Warehouse ID',
            'product_id' => 'Product ID',
            'qty' => 'Qty',
            'item_value' => 'Item Value',
            'trans_value' => 'Trans Value',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMovement()
    {
        return $this->hasOne(GoodMovement::className(), ['id' => 'movement_id']);
    }
}
