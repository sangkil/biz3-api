<?php

namespace biz\core\sales\components;

use Yii;
use biz\core\sales\models\Sales as MSales;
use yii\helpers\ArrayHelper;

/**
 * Description of Sales
 *
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class Sales extends \biz\core\base\Api
{

    /**
     *
     * @var string 
     */
    public $modelClass = 'biz\core\sales\models\Sales';

    /**
     *
     * @var string 
     */
    public $prefixEventName = 'e_sales';

    /**
     *
     * @param  array $data
     * @param  \biz\core\sales\models\Sales $model
     * @return \biz\core\sales\models\Sales
     * @throws \Exception
     */
    public function create($data, $model = null)
    {
        /* @var $model MSales */
        $model = $model ? : $this->createNewModel();
        $success = false;
        $model->scenario = MSales::SCENARIO_DEFAULT;
        $model->load($data, '');
        $this->fire('_create', [$model]);
        if (!empty($post['details'])) {
            $success = $model->save();
            $success = $model->saveRelated('salesDtls', $data, $success, 'details');
            if ($success) {
                $this->fire('_created', [$model]);
            } else {
                if ($model->hasRelatedErrors('salesDtls')) {
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
     * @param  string $id
     * @param  array $data
     * @param  \biz\core\sales\models\Sales $model
     * @return \biz\core\sales\models\Sales
     * @throws \Exception
     */
    public function update($id, $data, $model = null)
    {
        $model = $model ? : $this->findModel($id);

        $success = false;
        $model->scenario = MSales::SCENARIO_DEFAULT;
        $model->load($data, '');
        $this->fire('_update', [$model]);

        if (!isset($data['details']) || $data['details'] !== []) {
            $success = $model->save();
            if (!empty($data['details'])) {
                $success = $model->saveRelated('salesDtls', $data, $success, 'details');
            }
            if ($success) {
                $this->fire('_updated', [$model]);
            } else {
                if ($model->hasRelatedErrors('salesDtls')) {
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
     * @param  string $id
     * @param  array $data
     * @param  \biz\core\sales\models\Sales $model
     * @return mixed
     * @throws \Exception
     */
    public function release($id, $data = [], $model = null)
    {
        $model = $model ? : $this->findModel($id);

        $success = true;
        $model->scenario = MSales::SCENARIO_DEFAULT;
        $model->load($data, '');
        $model->status = MSales::STATUS_RELEASE;
        $this->fire('_release', [$model]);
        $salesDtls = ArrayHelper::index($model->salesDtls, 'product_id');
        if (!empty($data['details'])) {
            $this->fire('_release_head', [$model]);
            foreach ($data['details'] as $dataDetail) {
                $index = $dataDetail['product_id'];
                $detail = $salesDtls[$index];
                $detail->scenario = MSales::SCENARIO_RELEASE;
                $detail->load($dataDetail, '');
                $success = $success && $detail->save();
                $this->fire('_release_body', [$model, $detail]);
                $salesDtls[$index] = $detail;
            }
            $model->populateRelation('salesDtls', array_values($salesDtls));
            $this->fire('_release_end', [$model]);
        }
        $allReleased = true;
        foreach ($salesDtls as $detail) {
            $allReleased = $allReleased && $detail->sales_qty == $detail->sales_total_release;
        }
        if ($allReleased) {
            $model->status = MSales::STATUS_RELEASED;
        }
        if ($success && $model->save()) {
            $this->fire('_released', [$model]);
        } else {
            $success = false;
        }

        return $this->processOutput($success, $model);
    }
}
