<?php

namespace biz\core\sales\models;

use Yii;
use biz\core\master\models\ProductUom;

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
 */
class SalesDtl extends \yii\db\ActiveRecord
{
    /**
     * @var integer Warehouse for release.
     */
    public $warehouse_id;

    /**
     * @var double Quantity for release.
     */
    public $qty_release;

    /**
     * @var integer Uom for receive.
     */
    public $uom_id_release;

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
            [['id_sales', 'product_id', 'uom_id', 'sales_qty', 'sales_price', 'cogs'], 'required'],
            [['id_sales', 'product_id', 'uom_id'], 'integer'],
            [['qty', 'price', 'cogs', 'discount', 'tax'], 'number'],
            [['warehouse_id', 'qty_release', 'uom_id_release'], 'safe', 'on' => Sales::SCENARIO_RELEASE],
            [['warehouse_id'], 'required', 'on' => Sales::SCENARIO_RELEASE, 'when' => [$this, 'whenReleased']],
            [['qty_receive'], 'double', 'on' => Sales::SCENARIO_RELEASE],
            [['qty_receive'], 'convertRelease', 'on' => Sales::SCENARIO_RELEASE]
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

    public function convertRelease($attribute)
    {
        if ($this->uom_id_release === null || $this->uom_id == $this->uom_id_release) {
            $this->total_release += $this->qty_release;
        } else {
            $uoms = ProductUom::find()->where(['product_id' => $this->product_id])->indexBy('uom_id')->all();
            $this->total_release += $this->qty_release * $uoms[$this->uom_id_release]->isi / $uoms[$this->uom_id]->isi;
        }
        if ($this->total_release > $this->qty) {
            $this->addError($attribute, 'Total qty release large than sales qty');
        }
    }

    /**
     * Check when purchase is received.
     * Indicated with `qty_receive` is setted.
     *
     * @return boolean
     */
    public function whenReleased()
    {
        return $this->qty_release !== null && $this->qty_release !== '';
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
