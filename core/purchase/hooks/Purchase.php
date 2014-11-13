<?php

namespace biz\core\purchase\hooks;

use Yii;
use biz\core\inventory\models\GoodMovement as MGoodMovement;
use biz\core\purchase\models\Purchase as MPurchase;
use yii\helpers\ArrayHelper;

/**
 * Purchase
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 3.0
 */
class Purchase extends \yii\base\Behavior
{

    public function events()
    {
        return [
            'e_good-movement_applied' => 'goodMovementApplied',
        ];
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
        if (!in_array($model->reff_type, [100])) {
            return;
        }

        $purchase = MPurchase::findOne($model->reff_id);
        $purchaseDtls = ArrayHelper::index($purchase->purchaseDtls, 'product_id');
        // change total qty for reff document
        /* @var $purcDtl \biz\core\purchase\models\PurchaseDtl */
        foreach ($model->goodMovementDtls as $detail) {
            $purcDtl = $purchaseDtls[$detail->product_id];
            $purcDtl->total_receive += $detail->qty;
            $purcDtl->save(false);
        }
        $complete = true;
        foreach ($purchaseDtls as $purcDtl) {
            if ($purcDtl->total_receive != $purcDtl->qty) {
                $complete = false;
                break;
            }
        }
        if ($complete) {
            $purchase->status = MPurchase::STATUS_RECEIVED;
            $purchase->save(false);
        }  elseif($model->status == MPurchase::STATUS_DRAFT) {
            $purchase->status = MPurchase::STATUS_RECEIVE;
            $purchase->save(false);
        }
    }
}