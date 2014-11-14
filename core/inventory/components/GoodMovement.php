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
        $model->load($data, '');
        if (!empty($data['details'])) {
            $this->fire('_create', [$model]);
            $model->goodMovementDtls = $data['details'];
            $success = $model->save();
            if ($success) {
                $this->fire('_created', [$model]);
            }
        } else {
            $model->validate();
            $model->addError('goodMovementDtls', 'Details cannot be blank');
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
        if ($model->status != MGoodMovement::STATUS_DRAFT) {
            throw new ServerErrorHttpException('Document can not be update');
        }
        $success = false;
        $model->load($data, '');
        if (!isset($data['details']) || $data['details'] !== []) {
            $this->fire('_update', [$model]);
            if (!empty($data['details'])) {
                $model->goodMovementDtls = $data['details'];
            }
            $success = $model->save();
            if ($success) {
                $this->fire('_updated', [$model]);
            }
        } else {
            $model->validate();
            $model->addError('goodMovementDtls', 'Details cannot be blank');
        }

        return $this->processOutput($success, $model);
    }

    /**
     * Apply good movement.
     * Update stock
     */
    public function apply($id, $model = null)
    {
        /* @var $model MGoodMovement */
        $model = $model ? : $this->findModel($id);
        if ($model->status != MGoodMovement::STATUS_DRAFT) {
            throw new ServerErrorHttpException('Document can not be applied');
        }
        $model->status = MGoodMovement::STATUS_APPLIED;
        if ($model->save()) {
            $this->fire('_applied', [$model]);
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function delete($id, $model = null)
    {
        /* @var $model MGoodMovement */
        $model = $model ? : $this->findModel($id);
        if ($model->status != MGoodMovement::STATUS_DRAFT) {
            throw new ServerErrorHttpException('Document can not be delete');
        }
        return parent::delete($id, $model);
    }
}