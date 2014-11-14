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
 * @property \yii\db\ActiveRecord $reffDoc
 * @property \yii\db\ActiveRecord[] $reffDocDtls
 * @property GoodMovementDtl[] $goodMovementDtls
 * 
 * @property array $reffConfig
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>  
 * @since 3.0
 */
class GoodMovement extends \yii\db\ActiveRecord
{
    // status GoodMovement
    const STATUS_DRAFT = 10;
    const STATUS_APPLIED = 20;
    const STATUS_INVOICED = 30;
    const STATUS_CLOSED = 40;
    // type movement
    const TYPE_RECEIVE = 10;
    const TYPE_ISSUE = 20;

    /**
     * @var array 
     */
    public static $reffTypes;

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
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['reff_type'], 'resolveType'],
            [['date', 'warehouse_id', 'type'], 'required'],
            [['date', 'created_at', 'updated_at'], 'safe'],
            [['reff_type', 'reff_id', 'warehouse_id', 'status', 'created_by', 'updated_by'], 'integer'],
            [['number'], 'string', 'max' => 16],
            [['description'], 'string', 'max' => 255],
            [['reff_id'], 'unique', 'targetAttribute' => ['reff_id', 'reff_type', 'status'],
                'when' => function($obj) {
                return $obj->status == self::STATUS_DRAFT && $obj->reff_type != null;
            }
            ]
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
     * Get reference configuration
     * @param type $reff_type
     * @return null
     */
    public static function reffConfig($reff_type)
    {
        if (isset(static::$reffTypes[$reff_type])) {
            return static::$reffTypes[$reff_type];
        } else {
            return null;
        }
    }

    public function getReffConfig()
    {
        return static::reffConfig($this->reff_type);
    }

    /**
     * Set type of document depending reference document
     */
    public function resolveType()
    {
        if (isset(static::$reffTypes[$this->reff_type])) {
            $this->type = static::$reffTypes[$this->reff_type]['type'];
        } else {
            $this->addError('reff_type', "Reference type {$this->reff_type} not recognize");
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReffDoc()
    {
        $config = static::reffConfig($this->reff_type);
        if ($config && isset($config['class'])) {
            return $this->hasOne($config['class'], ['id' => 'reff_id']);
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReffDocDtls()
    {
        if (($reff = $this->reffDoc) !== null) {
            $config = static::reffConfig($this->reff_type);
            $relation = $reff->getRelation($config['relation']);
            return $this->hasMany($relation->modelClass, $relation->link)
                    ->via('reffDoc')
                    ->indexBy('product_id');
        }
        return null;
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
            [
                'class' => 'mdm\behaviors\ar\RelatedBehavior',
            ],
        ];
    }
}
// Load refference
GoodMovement::$reffTypes = require(__DIR__ . DIRECTORY_SEPARATOR . 'reff_types.php');

