<?php

namespace biz\core\accounting\controllers;

/**
 * Description of GLController
 *
 * @author Misbahul D Munir (mdmunir) <misbahuldmunir@gmail.com>
 */
class GLController extends \biz\core\base\rest\Controller
{
    /**
     * @inheritdoc
     */
    public $helperClass = 'biz\core\accounting\components\GL';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['update'], $actions['delete']);

        return $actions;
    }
}