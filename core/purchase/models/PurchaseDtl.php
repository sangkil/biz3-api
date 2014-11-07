<?php

namespace biz\core\purchase\models;

use Yii;

/**
 * This is the model class for table "{{%purchase_dtl}}".
 *
 * @property integer $purchase_id
 * @property integer $product_id
 * @property integer $uom_id
 * @property double $qty
 * @property double $price
 * @property double $discount
 * @property double $total_receive
 *
 * @property Purchase $purchase
 */
class PurchaseDtl extends \yii\db\ActiveRecord
{
    /**
     * @var double Quantity for receive.
     */
    public $receive;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%purchase_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['purchase_id', 'product_id', 'uom_id', 'qty', 'price'], 'required'],
            [['purchase_id', 'product_id', 'uom_id'], 'integer'],
            [['qty', 'price'], 'number'],
            [['receive'], 'double', 'on' => Purchase::SCENARIO_RECEIVE],
            [['receive'], 'checkQty', 'on' => Purchase::SCENARIO_RECEIVE],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();

        foreach ($scenarios[Purchase::SCENARIO_RECEIVE] as $i => $attr) {
            if (!in_array($attr, ['receive',]) && $attr[0] != '!') {
                $scenarios[Purchase::SCENARIO_RECEIVE][$i] = '!' . $attr;
            }
        }

        return $scenarios;
    }

    public function checkQty($attribute)
    {
        if ($this->receive > $this->qty - $this->total_receive) {
            $this->addError($attribute, 'Total qty receive large than purch qty');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'purchase_id' => 'Purchase ID',
            'product_id' => 'Product ID',
            'uom_id' => 'Uom ID',
            'qty' => 'Qty',
            'price' => 'Price',
            'discount' => 'Discount',
            'total_receive' => 'Qty Receive',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPurchase()
    {
        return $this->hasOne(Purchase::className(), ['id' => 'purchase_id']);
    }
}