<?php

namespace biz\core\inventory\models;

use Yii;

/**
 * This is the model class for table "stock_opname_dtl".
 *
 * @property integer $id_opname
 * @property integer $id_product
 * @property integer $id_uom
 * @property double $qty
 *
 * @property StockOpname $stockOpname
 */
class StockOpnameDtl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%stock_opname_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_opname', 'id_product', 'id_uom', 'qty'], 'required'],
            [['id_opname', 'id_product', 'id_uom'], 'integer'],
            [['qty'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_opname' => 'Id Opname',
            'id_product' => 'Id Product',
            'id_uom' => 'Id Uom',
            'qty' => 'Qty',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStockOpname()
    {
        return $this->hasOne(StockOpname::className(), ['id_opname' => 'id_opname']);
    }
}
