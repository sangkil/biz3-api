<?php

namespace biz\core\inventory\models;

use Yii;

/**
 * This is the model class for table "{{%good_movement}}".
 *
 * @property integer $id
 * @property string $number
 * @property string $date
 * @property integer $type
 * @property integer $reff_type
 * @property integer $reff_id
 * @property string $description
 * @property integer $warehouse_id
 * @property integer $status
 * @property string $created_at
 * @property integer $created_by
 * @property string $updated_at
 * @property integer $updated_by
 *
 * @property GoodMovementDtl[] $goodMovementDtls
 */
class GoodMovement extends \yii\db\ActiveRecord
{
    // source document
    const TYPE_PURCHASE = 100;
    const TYPE_SALES = 200;
    const TYPE_TRANSFER_RECEIVE = 300;
    const TYPE_TRANSFER_ISSUE = 400;
    // status GoodMovement
    const STATUS_OPEN = 1;
    const STATUS_CLOSE = 2;
    // type movement
    const TYPE_RECEIVE = 1;
    const TYPE_ISSUE = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%good_movement}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['date', 'warehouse_id', 'type'], 'required'],
            [['date', 'created_at', 'updated_at'], 'safe'],
            [['reff_type', 'reff_id', 'warehouse_id', 'status', 'created_by', 'updated_by'], 'integer'],
            [['number'], 'string', 'max' => 16],
            [['description'], 'string', 'max' => 255]
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
            'type' => 'Type',
            'warehouse_id' => 'Warehouse ID',
            'reff_type' => 'Reff Type',
            'reff_id' => 'Reff ID',
            'description' => 'Description',
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
    public function getGoodMovementDtls()
    {
        return $this->hasMany(GoodMovementDtl::className(), ['movement_id' => 'id']);
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
                'value' => 'IM' . date('ymd.?')
            ],
            'BizStatusConverter',
            'mdm\behaviors\ar\RelatedBehavior',
        ];
    }
}