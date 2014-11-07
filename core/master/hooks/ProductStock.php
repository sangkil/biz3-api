<?php

namespace biz\core\master\hooks;

use biz\core\master\models\ProductStock as MProductStock;
use biz\core\master\models\ProductUom;
use biz\core\base\NotFoundException;
use yii\base\UserException;
use biz\core\master\models\Cogs;
use biz\core\inventory\models\GoodMovement as MGoodMovement;
use yii\helpers\ArrayHelper;

/**
 * Description of Stock
 *
 * @author MDMunir
 */
class ProductStock extends \yii\base\Behavior
{

    public function events()
    {
        return [
            'e_good-movement_applied' => 'goodMovementApplied',
            'e_purchase_receive_body' => 'purchaseReceiveBody',
            'e_sales_release_body' => 'salesReleaseBody',
            'e_transfer_release_body' => 'transferReleaseBody',
            'e_transfer_receive_body' => 'transferReceiveBody',
            'e_transfer_complete_body' => 'transferCompleteBody',
            'e_stock-adjustment_applied' => 'adjustmentApplied'
        ];
    }

    /**
     *
     * @param  array $params Required field warehouse_id, product_id, qty
     * Optional field app, reff_id, uom_id, item_value
     * @return boolean
     * @throws UserException
     */
    public function updateStock($params)
    {
        $stock = MProductStock::findOne([
                'warehouse_id' => $params['warehouse_id'],
                'product_id' => $params['product_id'],
        ]);
        if (isset($params['uom_id'])) {
            $qty_per_uom = ProductUom::find()->select('isi')
                    ->where([
                        'product_id' => $params['product_id'],
                        'uom-id' => $params['uom-id']
                    ])->scalar();
            if ($qty_per_uom === false) {
                throw new NotFoundException("Uom '{$params['uom-id']}' not found for product '{$params['product_id']}'");
            }
        } else {
            $qty_per_uom = 1;
        }

        if (!$stock) {
            $stock = new MProductStock([
                'warehouse_id' => $params['warehouse_id'],
                'product_id' => $params['product_id'],
                'qty' => 0,
            ]);
        }
        // update cogs
        if (isset($params['price'])) {
            $params['qty_per_uom'] = $qty_per_uom;
            $this->updateCogs($params);
        }

        $stock->qty_stock = $stock->qty_stock + $params['qty'] * $qty_per_uom;
        if ($stock->canSetProperty('logParams')) {
            $logParams = ['mv_qty' => $params['qty'] * $qty_per_uom];
            foreach (['app', 'reff_id'] as $key) {
                if (isset($params[$key]) || array_key_exists($key, $params)) {
                    $logParams[$key] = $params[$key];
                }
            }
            $stock->logParams = $logParams;
        }
        if (!$stock->save()) {
            throw new UserException(implode(",\n", $stock->firstErrors));
        }

        return true;
    }

    protected function updateCogs($params)
    {
        $cogs = Cogs::findOne(['product_id' => $params['product_id']]);
        if (!$cogs) {
            $cogs = new Cogs([
                'product_id' => $params['product_id'],
                'cogs' => 0.0
            ]);
        }
        $current_stock = MProductStock::find()
            ->where(['product_id' => $params['product_id']])
            ->sum('qty_stock');
        $qty_per_uom = $params['qty_per_uom'];
        $added_stock = $params['qty'] * $qty_per_uom;
        if ($current_stock + $added_stock != 0) {
            $cogs->cogs = 1.0 * ($cogs->cogs * $current_stock + $params['price'] * $params['qty']) / ($current_stock + $added_stock);
        } else {
            $cogs->cogs = 0;
        }
        if ($cogs->canSetProperty('logParams')) {
            $cogs->logParams = [
                'app' => $params['app'],
                'reff_id' => $params['reff_id'],
            ];
        }
        if (!$cogs->save()) {
            throw new UserException(implode(",\n", $cogs->firstErrors));
        }

        return true;
    }

    /**
     * Handler for Good Movement created.
     * It used to update stock
     * @param \biz\core\base\Event $event
     */
    public function goodMovementApplied($event)
    {
        /* @var $model MGoodMovement */
        $model = $event->params[0];
        $warehouse_id = $model->warehouse_id;
        $config = MGoodMovement::reffConfig($model->reff_type);
        if ($config && isset($config['uom_field'])) {
            $class = $config['class'];
            $reffModel = $class::findOne($model->reff_id);
            $populations = ArrayHelper::index($reffModel->{$config['relation']}, 'product_id');
        } else {
            $populations = [];
        }
        foreach ($model->goodMovementDtls as $detail) {
            $params = [
                'warehouse_id' => $warehouse_id,
                'product_id' => $detail->product_id,
                'qty' => $detail->qty,
                'app' => 'good_movement',
                'price' => $detail->item_value,
                'reff_id' => $detail->movement_id,
            ];
            if (isset($populations[$detail->product_id])) {
                $params['uom_id'] = $populations[$detail->product_id]->{$config['uom_field']};
            }
            $this->updateStock($params);
        }
    }

    /**
     * @param \biz\core\base\Event $event
     */
    public function purchaseReceiveBody($event)
    {
        if (isset($event->sender->goodMovementImplemented)) {
            return;
        }
        /* @var $detail \biz\core\purchase\models\PurchaseDtl */
        $detail = $event->params[1];
        $this->updateStock([
            'warehouse_id' => $detail->warehouse_id,
            'product_id' => $detail->product_id,
            'uom_id' => $detail->uom_id_receive? : $detail->uom_id,
            'price' => $detail->price,
            'qty' => $detail->receive,
            'app' => 'purchase',
            'reff_id' => $detail->purchase_id,
        ]);
    }

    /**
     *
     * @param \biz\core\base\Event $event
     */
    public function salesReleaseBody($event)
    {
        if (isset($event->sender->goodMovementImplemented)) {
            return;
        }
        /* @var $detail \biz\core\sales\models\SalesDtl */
        $detail = $event->params[1];
        $this->updateStock([
            'warehouse_id' => $detail->warehouse_id,
            'product_id' => $detail->product_id,
            'uom_id' => $detail->uom_id_release? : $detail->uom_id,
            'qty' => -$detail->total_release,
            'app' => 'sales',
            'reff_id' => $detail->id_sales_dtl,
        ]);
    }

    /**
     *
     * @param \biz\core\base\Event $event
     */
    public function transferReleaseBody($event)
    {
        if (isset($event->sender->goodMovementImplemented)) {
            return;
        }
        /* @var $detail \biz\core\inventory\models\TransferDtl */
        $detail = $event->params[1];
        $this->updateStock([
            'warehouse_id' => $detail->warehouse_id_src,
            'product_id' => $detail->product_id,
            'uom_id' => $detail->uom_id_send? : $detail->uom_id,
            'qty' => -$detail->qty_send,
            'app' => 'transfer_release',
            'reff_id' => $detail->id_transfer,
        ]);
    }

    /**
     *
     * @param \biz\core\base\Event $event
     */
    public function transferReceiveBody($event)
    {
        if (isset($event->sender->goodMovementImplemented)) {
            return;
        }
        /* @var $detail \biz\core\inventory\models\TransferDtl */
        $detail = $event->params[1];
        $this->updateStock([
            'warehouse_id' => $detail->warehouse_id_dest,
            'product_id' => $detail->product_id,
            'uom_id' => $detail->uom_id_receive? : $detail->uom_id,
            'qty' => $detail->total_receive,
            'app' => 'transfer_receive',
            'reff_id' => $detail->id_transfer,
        ]);
    }

    /**
     *
     * @param \biz\core\base\Event $event
     */
    public function transferCompleteBody($event)
    {
        if (isset($event->sender->goodMovementImplemented)) {
            return;
        }
        /* @var $detail \biz\core\inventory\models\TransferDtl */
        $detail = $event->params[1];
        if (!empty($detail->qty_send)) {
            $this->updateStock([
                'warehouse_id' => $detail->warehouse_id_src,
                'product_id' => $detail->product_id,
                'uom_id' => $detail->uom_id_send? : $detail->uom_id,
                'qty' => -$detail->qty_send,
                'app' => 'transfer_complete',
                'reff_id' => $detail->id_transfer,
            ]);
        }
        if (!empty($detail->total_receive)) {
            $this->updateStock([
                'warehouse_id' => $detail->warehouse_id_dest,
                'product_id' => $detail->product_id,
                'uom_id' => $detail->uom_id_receive? : $detail->uom_id,
                'qty' => $detail->total_receive,
                'app' => 'transfer_complete',
                'reff_id' => $detail->id_transfer,
            ]);
        }
    }

    /**
     *
     * @param \biz\core\base\Event $event
     */
    public function adjustmentApplied($event)
    {
        if (isset($event->sender->goodMovementImplemented)) {
            return;
        }
        /* @var $model \biz\core\inventory\models\StockAdjustment */
        $model = $event->params[0];
        foreach ($model->stockAdjustmentDtls as $detail) {
            $this->updateStock([
                'warehouse_id' => $detail->warehouse_id,
                'product_id' => $detail->product_id,
                'qty' => $detail->qty,
                'app' => 'stock_adjustment',
                'price' => $detail->item_value,
                'reff_id' => $detail->id_adjustment,
            ]);
        }
    }
}