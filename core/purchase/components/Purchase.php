<?php

namespace biz\core\purchase\components;

use Yii;
use biz\core\purchase\models\Purchase as MPurchase;
use yii\helpers\ArrayHelper;

/**
 * Description of Purchase
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>  
 * @since 3.0
 */
class Purchase extends \biz\core\base\Api
{
    /**
     * @inheritdoc
     */
    public $modelClass = 'biz\core\purchase\models\Purchase';

    /**
     * @inheritdoc
     */
    public $prefixEventName = 'e_purchase';

    /**
     * Use to create purchase.
     * @param array $data values use to create purchase model. It must contain
     *
     * @param \biz\core\purchase\models\Purchase $model
     *
     * @return \biz\core\purchase\models\Purchase
     * @throws \Exception
     */
    public function create($data, $model = null)
    {
        /* @var $model MPurchase */
        $model = $model ? : $this->createNewModel();
        $success = false;
        $model->load($data, '');

        if (!empty($data['details'])) {
            $this->fire('_create', [$model]);
            $success = $model->save();
            $success = $model->saveRelated('purchaseDtls', $data, $success, 'details');
            if ($success) {
                $this->fire('_created', [$model]);
            } else {
                if ($model->hasRelatedErrors('purchaseDtls')) {
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
     * Use to update existing purchase.
     * @param array $data values use to create purchase model. It must contain
     *
     * @param \biz\core\purchase\models\Purchase $model
     *
     * @return \biz\core\purchase\models\Purchase
     * @throws \Exception
     */
    public function update($id, $data, $model = null)
    {
        $model = $model ? : $this->findModel($id);

        $success = false;
        $model->load($data, '');

        if (!isset($data['details']) || $data['details'] !== []) {
            $this->fire('_update', [$model]);
            $success = $model->save();
            if (!empty($data['details'])) {
                $success = $model->saveRelated('purchaseDtls', $data, $success, 'details');
            }
            if ($success) {
                $this->fire('_updated', [$model]);
            } else {
                if ($model->hasRelatedErrors('purchaseDtls')) {
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
     * Purchase receive
     * @param  string $id
     * @param  array $data
     * @param  \biz\core\purchase\models\Purchase $model
     * @return \biz\core\purchase\models\Purchase
     * @throws \Exception
     */
    public function receive($id, $data = [], $model = null)
    {
        $model = $model ? : $this->findModel($id);
        /* @var $detail \biz\core\purchase\models\PurchaseDtl */
        $success = true;
        $model->load($data, '');
        $model->status = MPurchase::STATUS_RECEIVE;
        $model->scenario = MPurchase::SCENARIO_RECEIVE;

        $this->fire('_receive', [$model]);
        $purchaseDtls = ArrayHelper::index($model->purchaseDtls, 'product_id');
        if (!empty($data['details'])) {
            foreach ($data['details'] as $dataDetail) {
                $index = $dataDetail['product_id'];
                $detail = $purchaseDtls[$index];
                $detail->scenario = MPurchase::SCENARIO_RECEIVE;
                $detail->load($dataDetail, '');
                $success = $success && $detail->validate();
                $purchaseDtls[$index] = $detail;
            }
            $model->populateRelation('purchaseDtls', array_values($purchaseDtls));
        } else {
            $model->validate();
            $model->addError('details', 'Details cannot be blank');
            $success = false;
        }
        if ($success && $model->save()) {
            $this->fire('_received', [$model]);
        } else {
            $success = false;
        }

        return $this->processOutput($success, $model);
    }
}