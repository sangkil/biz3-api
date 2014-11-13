<?php

use biz\core\inventory\models\GoodMovement;

/*
 * Avaliable source for good movement
 */

return[
    // Purchase receive
    100 => [
        'type' => GoodMovement::TYPE_RECEIVE,
        'class' => 'biz\core\purchase\models\Purchase',
        'relation' => 'purchaseDtls',
        'apply_method' => 'applyGR',
        'uom_field' => 'uom_id',
    ],
];
