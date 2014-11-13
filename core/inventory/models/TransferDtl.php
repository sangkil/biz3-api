<?php

namespace biz\core\inventory\models;

use Yii;

/**
 * This is the model class for table "{{%transfer_dtl}}".
 *
 * @property integer $transfer_id
 * @property integer $product_id
 * @property integer $uom_id
 * @property double $qty
 * @property double $total_send
 * @property double $total_receive
 *
 * @property Transfer $transfer
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>  
 * @since 3.0
 */
class TransferDtl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transfer_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['transfer_id', 'product_id', 'uom_id'], 'required'],
            [['transfer_id', 'product_id', 'uom_id'], 'integer'],
            [['qty', 'total_send', 'total_receive'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transfer_id' => 'Transfer ID',
            'product_id' => 'Product ID',
            'uom_id' => 'Uom ID',
            'qty' => 'Qty',
            'total_send' => 'Qty Send',
            'total_receive' => 'Qty Receive',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer()
    {
        return $this->hasOne(Transfer::className(), ['id' => 'transfer_id']);
    }
}
