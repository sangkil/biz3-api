<?php

namespace biz\core\sales\models;

use Yii;

/**
 * This is the model class for table "{{%sales_dtl}}".
 *
 * @property integer $sales_id
 * @property integer $product_id
 * @property integer $uom_id
 * @property double $qty
 * @property double $price
 * @property double $total_release
 * @property double $cogs
 * @property double $discount
 * @property double $tax
 *
 * @property Sales $sales
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>  
 * @since 3.0
 */
class SalesDtl extends \yii\db\ActiveRecord
{
    /**
     * @var double Quantity for release.
     */
    public $release;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sales_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_id', 'uom_id', 'qty', 'price', 'cogs'], 'required'],
            [['sales_id', 'product_id', 'uom_id'], 'integer'],
            [['qty', 'price', 'cogs', 'discount', 'tax'], 'number'],
            [['release'], 'double', 'on' => Sales::SCENARIO_RELEASE],
            [['release'], 'checkQty', 'on' => Sales::SCENARIO_RELEASE]
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();

        foreach ($scenarios[Sales::SCENARIO_RELEASE] as $i => $attr) {
            if (!in_array($attr, ['warehouse_id', 'qty_release', 'uom_id_release']) && $attr[0] != '!') {
                $scenarios[Sales::SCENARIO_RELEASE][$i] = '!' . $attr;
            }
        }

        return $scenarios;
    }

    public function checkQty($attribute)
    {
        if ($this->release > $this->qty - $this->total_release) {
            $this->addError($attribute, 'Total qty release large than purch qty');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sales_id' => 'Sales ID',
            'product_id' => 'Product ID',
            'uom_id' => 'Uom ID',
            'qty' => 'Qty',
            'price' => 'Price',
            'total_release' => 'Qty Release',
            'cogs' => 'Cogs',
            'discount' => 'Discount',
            'tax' => 'Tax',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSales()
    {
        return $this->hasOne(Sales::className(), ['id' => 'sales_id']);
    }
}
