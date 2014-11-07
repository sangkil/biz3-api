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
        'qty_field' => 'qty',
        'total_field' => 'total_receive',
        'uom_field' => 'uom_id',
        'status_complete' => 3
    ],
];
