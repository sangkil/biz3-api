<?php

use biz\core\inventory\models\GoodsMovement;

/*
 * Avaliable source for good movement
 */

return[
    // Purchase receive
    100 => [
        'type' => GoodsMovement::TYPE_RECEIVE,
        'class' => 'biz\core\purchase\models\Purchase',
        'relation' => 'purchaseDtls',
        'apply_method' => 'applyGR',
    ],
    // Purchase receive
    200 => [
        'type' => GoodsMovement::TYPE_ISSUE,
        'class' => 'biz\core\sales\models\Sales',
        'relation' => 'salesDtls',
        'apply_method' => 'applyGI',
    ],
];
