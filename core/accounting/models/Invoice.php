<?php

namespace biz\core\accounting\models;

use Yii;

/**
 * This is the model class for table "{{%invoice}}".
 *
 * @property integer $id
 * @property string $number
 * @property string $date
 * @property string $due_date
 * @property integer $type
 * @property integer $vendor_id
 * @property double $value
 * @property integer $status
 * @property string $created_at
 * @property integer $created_by
 * @property string $updated_at
 * @property integer $updated_by
 *
 * @property PaymentDtl[] $paymentDtls
 * @property Payment[] $payments
 * @property InvoiceDtl[] $invoiceDtls
 */
class Invoice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%invoice}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['number', 'date', 'due_date', 'type', 'vendor_id', 'value', 'status'], 'required'],
            [['date', 'due_date', 'created_at', 'updated_at'], 'safe'],
            [['type', 'vendor_id', 'status', 'created_by', 'updated_by'], 'integer'],
            [['value'], 'number'],
            [['number'], 'string', 'max' => 16]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'number' => 'Number',
            'date' => 'Date',
            'due_date' => 'Due Date',
            'type' => 'Type',
            'vendor_id' => 'Vendor ID',
            'value' => 'Value',
            'status' => 'Status',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaymentDtls()
    {
        return $this->hasMany(PaymentDtl::className(), ['invoice_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::className(), ['id' => 'payment_id'])->viaTable('{{%payment_dtl}}', ['invoice_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceDtls()
    {
        return $this->hasMany(InvoiceDtl::className(), ['invoice_id' => 'id']);
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return[
            'BizTimestampBehavior',
            'BizBlameableBehavior',
            [
                'class' => 'mdm\autonumber\Behavior',
                'digit' => 6,
                'attribute' => 'number',
                'value' => 'AI' . date('y.?')
            ],
            [
                'class' => 'mdm\converter\DateConverter',
                'attributes' => [
                    'Date' => 'date',
                    'DueDate' => 'due_date',
                ]
            ],
            'BizStatusConverter',
            'mdm\behaviors\ar\RelatedBehavior',
        ];
    }
}
