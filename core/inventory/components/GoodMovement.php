<?php

namespace biz\core\inventory\components;

use Yii;
use biz\core\inventory\models\GoodMovement as MGoodMovement;
use yii\web\ServerErrorHttpException;

/**
 * Description of GoodMovement
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>  
 * @since 3.0
 */
class GoodMovement extends \biz\core\base\Api
{
    /**
     * @var string 
     */
    public $modelClass = 'biz\core\inventory\models\GoodMovement';

    /**
     * @var string 
     */
    public $prefixEventName = 'e_good-movement';

    /**
     *
     * @param  array                                $data
     * @param  \biz\core\inventory\models\GoodMovement $model
     * @return \biz\core\inventory\models\GoodMovement
     */
    public function create($data, $model = null)
    {
        /* @var $model MGoodMovement */
        $model = $model ? : $this->createNewModel();
        $success = false;
        $model->scenario = MGoodMovement::SCENARIO_DEFAULT;
        $model->load($data, '');
        if (!empty($data['details'])) {
            $data['details'] = array_filter($data['details'], function($val) {
                return !empty($val['qty']);
            });
        }
        if (!empty($data['details'])) {
            $this->fire('_create', [$model]);
            $success = $model->save();
            $success = $model->saveRelated('goodMovementDtls', $data, $success, 'details');
            if ($success) {
                $this->fire('_created', [$model]);
            } else {
                if ($model->hasRelatedErrors('goodMovementDtls')) {
                    $model->addError('details', 'Details validation error');
                }
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }

        return $this->processOutput($success, $model);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data, $model = null)
    {
        /* @var $model MGoodMovement */
        $model = $model ? : $this->findModel($id);
        if ($model->status != MGoodMovement::STATUS_OPEN) {
            throw new ServerErrorHttpException('Document can not be update');
        }
        $success = false;
        $model->scenario = MGoodMovement::SCENARIO_DEFAULT;
        $model->load($data, '');
        if (!empty($data['details'])) {
            $data['details'] = array_filter($data['details'], function($val) {
                return !empty($val['qty']);
            });
        }
        if (!isset($data['details']) || $data['details'] !== []) {
            $this->fire('_update', [$model]);
            $success = $model->save();
            if (!empty($data['details'])) {
                $success = $model->saveRelated('goodMovementDtls', $data, $success, 'details');
            }
            if ($success) {
                $this->fire('_updated', [$model]);
            } else {
                if ($model->hasRelatedErrors('goodMovementDtls')) {
                    $model->addError('details', 'Details validation error');
                }
            }
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
        }

        return $this->processOutput($success, $model);
    }

    /**
     * 
     */
    public function apply($id, $model = null)
    {
        /* @var $model MGoodMovement */
        $model = $model ? : $this->findModel($id);
        if ($model->status != MGoodMovement::STATUS_OPEN) {
            throw new ServerErrorHttpException('Document can not be applied');
        }
        $model->status = MGoodMovement::STATUS_CLOSE;
        if ($model->save()) {
            $this->fire('_applied', [$model]);
        }
        return $this->processOutput($success, $model);
    }

    /**
     * @inheritdoc
     */
    public function delete($id, $model = null)
    {
        /* @var $model MGoodMovement */
        $model = $model ? : $this->findModel($id);
        if ($model->status != MGoodMovement::STATUS_OPEN) {
            throw new ServerErrorHttpException('Document can not be delete');
        }
        return parent::delete($id, $model);
    }
}