<?php

namespace biz\core\inventory\hooks;

use Yii;
use biz\core\base\Event;
use biz\core\inventory\models\GoodMovement as MGoodMovement;
use biz\core\inventory\components\GoodMovement as ApiGoodMovement;
use biz\core\purchase\models\Purchase;
use biz\core\sales\models\Sales;
use biz\core\inventory\models\Transfer;
use biz\core\master\models\ProductUom;
use yii\base\UserException;
use biz\core\inventory\models\StockAdjustment;

/**
 * Description of CreateTransferNotice
 *
 * @author MDMunir
 */
class GoodMovement extends \yii\base\Behavior
{
    public $goodMovementImplemented = true;

    public function events()
    {
        return [
            'e_purchase_receive_end' => 'purchaseReceive',
            'e_sales_release_end' => 'salesRelease',
            'e_transfer_release_end' => 'transferRelease',
            'e_transfer_receive_end' => 'transferReceive',
            'e_transfer_completed' => 'transferComplete',
            'e_stock-adjustment_applied' => 'adjustmentApplied'
        ];
    }

    protected function createMovementDoc($data)
    {
        /* @var $model MGoodMovement */
        $model = ApiGoodMovement::create($data);
        if ($model->hasErrors) {
            throw new UserException(implode("\n", $model->firstErrors));
        }
    }

    /**
     *
     * @param Event $event
     */
    public function purchaseReceive($event)
    {
        /* @var $model Purchase */
        $model = $event->params[0];
        $data = [
            'movement_type' => MGoodMovement::TYPE_PURCHASE,
            'id_reff' => $model->id_purchase
        ];
        $data['details'] = [];
        $query_isi = ProductUom::find()->select('isi');
        foreach ($model->purchaseDtls as $detail) {
            if (!empty($detail->qty_receive)) {
                $isi = $query_isi->where([
                        'id_product' => $detail->id_product,
                        'id_uom' => $detail->id_uom_receive? : $detail->id_uom])
                    ->scalar();
                $data['details'][] = [
                    'id_warehouse' => $detail->id_warehouse,
                    'id_product' => $detail->id_product,
                    'movement_qty' => $detail->qty_receive * $isi,
                    'item_value' => $detail->purch_price - $detail->discount,
                    'trans_value' => $detail->purch_price - $detail->discount,
                ];
            }
        }
        $this->createMovementDoc($data);
    }

    /**
     *
     * @param Event $event
     */
    public function salesRelease($event)
    {
        /* @var $model Sales */
        $model = $event->params[0];
        $data = [
            'movement_type' => MGoodMovement::TYPE_SALES,
            'id_reff' => $model->id_sales
        ];
        $data['details'] = [];
        $query_isi = ProductUom::find()->select('isi');
        foreach ($model->salesDtls as $detail) {
            if (!empty($detail->qty_release)) {
                $isi = $query_isi->where([
                        'id_product' => $detail->id_product,
                        'id_uom' => $detail->id_uom_release? : $detail->id_uom])
                    ->scalar();
                $data['details'][] = [
                    'id_warehouse' => $detail->id_warehouse,
                    'id_product' => $detail->id_product,
                    'movement_qty' => -$detail->qty_release * $isi,
                    'trans_value' => $detail->sales_price - $detail->discount,
                ];
            }
        }
        $this->createMovementDoc($data);
    }

    /**
     *
     * @param Event $event
     */
    public function transferRelease($event)
    {
        /* @var $model Transfer */
        $model = $event->params[0];
        $data = [
            'movement_type' => MGoodMovement::TYPE_TRANSFER_RELEASE,
            'id_reff' => $model->id_transfer
        ];
        $data['details'] = [];
        $query_isi = ProductUom::find()->select('isi');
        foreach ($model->transferDtls as $detail) {
            if (!empty($detail->qty_send)) {
                $isi = $query_isi->where([
                        'id_product' => $detail->id_product,
                        'id_uom' => $detail->id_uom_send ? : $detail->id_uom])
                    ->scalar();
                $data['details'][] = [
                    'id_warehouse' => $detail->id_warehouse_src,
                    'id_product' => $detail->id_product,
                    'movement_qty' => -$detail->qty_send * $isi,
                ];
            }
        }

        $this->createMovementDoc($data);
    }

    /**
     *
     * @param Event $event
     */
    public function transferReceive($event)
    {
        /* @var $model Transfer */
        $model = $event->params[0];
        $data = [
            'movement_type' => MGoodMovement::TYPE_TRANSFER_RECEIVE,
            'id_reff' => $model->id_transfer
        ];
        $data['details'] = [];
        $query_isi = ProductUom::find()->select('isi');
        foreach ($model->transferDtls as $detail) {
            if (!empty($detail->qty_receive)) {
                $isi = $query_isi->where([
                        'id_product' => $detail->id_product,
                        'id_uom' => $detail->id_uom_receive? : $detail->id_uom])
                    ->scalar();
                $data['details'][] = [
                    'id_warehouse' => $detail->id_warehouse_dest,
                    'id_product' => $detail->id_product,
                    'movement_qty' => $detail->qty_receive * $isi,
                ];
            }
        }

        $this->createMovementDoc($data);
    }

    /**
     *
     * @param Event $event
     */
    public function transferComplete($event)
    {
        /* @var $model Transfer */
        $model = $event->params[0];
        $data = [
            'movement_type' => MGoodMovement::TYPE_TRANSFER_COMPLETE,
            'id_reff' => $model->id_transfer
        ];
        $data['details'] = [];
        $query_isi = ProductUom::find()->select('isi');
        foreach ($model->transferDtls as $detail) {
            if (!empty($detail->qty_send)) {
                $isi = $query_isi->where([
                        'id_product' => $detail->id_product,
                        'id_uom' => $detail->id_uom_send? : $detail->id_uom])
                    ->scalar();
                $data['details'][] = [
                    'id_warehouse' => $detail->id_warehouse_src,
                    'id_product' => $detail->id_product,
                    'movement_qty' => -$detail->qty_send * $isi,
                ];
            }
            if (!empty($detail->qty_receive)) {
                $isi = $query_isi->where([
                        'id_product' => $detail->id_product,
                        'id_uom' => $detail->id_uom_receive? : $detail->id_uom])
                    ->scalar();
                $data['details'][] = [
                    'id_warehouse' => $detail->id_warehouse_dest,
                    'id_product' => $detail->id_product,
                    'movement_qty' => $detail->qty_receive * $isi,
                ];
            }
        }

        $this->createMovementDoc($data);
    }

    /**
     *
     * @param Event $event
     */
    public function adjustmentApplied($event)
    {
        /* @var $model StockAdjustment */
        $model = $event->params[0];
        $data = [
            'movement_type' => MGoodMovement::TYPE_ADJUSTMENT,
            'id_reff' => $model->id_adjustment
        ];
        $data['details'] = [];
        $query_isi = ProductUom::find()->select('isi');
        foreach ($model->stockAdjustmentDtls as $detail) {
            $isi = $query_isi->where([
                    'id_product' => $detail->id_product,
                    'id_uom' => $detail->id_uom])
                ->scalar();
            $data['details'][] = [
                'id_warehouse' => $model->id_warehouse,
                'id_product' => $detail->id_product,
                'movement_qty' => $detail->qty * $isi,
                'item_value' => $detail->item_value
            ];
        }

        $this->createMovementDoc($data);
    }
}
