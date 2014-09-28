<?php

namespace biz\core\accounting\models;

use Yii;

/**
 * This is the model class for table "entri_sheet_dtl".
 *
 * @property string $cd_esheet
 * @property string $cd_esheet_dtl
 * @property string $nm_esheet_dtl
 * @property integer $id_coa
 *
 * @property EntriSheet $entriSheet
 * @property Coa $coa
 */
class EntriSheetDtl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%entri_sheet_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cd_esheet', 'cd_esheet_dtl', 'nm_esheet_dtl', 'id_coa'], 'required'],
            [['id_coa'], 'integer'],
            [['cd_esheet', 'cd_esheet_dtl'], 'string', 'max' => 16],
            [['nm_esheet_dtl'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cd_esheet' => 'Cd Esheet',
            'cd_esheet_dtl' => 'Cd Esheet Dtl',
            'nm_esheet_dtl' => 'Nm Esheet Dtl',
            'id_coa' => 'Id Coa',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntriSheet()
    {
        return $this->hasOne(EntriSheet::className(), ['cd_esheet' => 'cd_esheet']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoa()
    {
        return $this->hasOne(Coa::className(), ['id_coa' => 'id_coa']);
    }
}
