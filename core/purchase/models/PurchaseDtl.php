<?php

namespace biz\core\purchase\models;

use Yii;
use biz\core\master\models\ProductUom;

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
     * @var integer Warehouse for receive.
     */
    public $warehouse_id;

    /**
     * @var double Quantity for receive.
     */
    public $receive;

    /**
     * @var integer Uom for receive.
     */
    public $uom_id_receive;

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
            [['warehouse_id', 'receive', 'uom_id_receive'], 'safe', 'on' => Purchase::SCENARIO_RECEIVE],
            [['warehouse_id'], 'required', 'on' => Purchase::SCENARIO_RECEIVE, 'when' => [$this, 'whenReceived']],
            [['receive'], 'double', 'on' => Purchase::SCENARIO_RECEIVE],
            [['receive'], 'convertReceive', 'on' => Purchase::SCENARIO_RECEIVE]
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();

        foreach ($scenarios[Purchase::SCENARIO_RECEIVE] as $i => $attr) {
            if (!in_array($attr, ['warehouse_id', 'receive', 'uom_id_receive']) && $attr[0] != '!') {
                $scenarios[Purchase::SCENARIO_RECEIVE][$i] = '!' . $attr;
            }
        }

        return $scenarios;
    }

    public function convertReceive($attribute)
    {
        if ($this->uom_id_receive === null || $this->uom_id == $this->uom_id_receive) {
            $this->total_receive += $this->receive;
        } else {
            $uoms = ProductUom::find()->where(['product_id' => $this->product_id])->indexBy('uom_id')->all();
            $this->total_receive += $this->receive * $uoms[$this->uom_id_receive]->isi / $uoms[$this->uom_id]->isi;
        }
        if ($this->total_receive > $this->qty) {
            $this->addError($attribute, 'Total qty receive large than purch qty');
        }
    }

    /**
     * Check when purchase is received.
     * Indicated with `total_receive` is setted.
     *
     * @return boolean
     */
    public function whenReceived()
    {
        return $this->receive !== null && $this->receive !== '';
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
