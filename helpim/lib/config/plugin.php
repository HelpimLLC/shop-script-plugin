<?php

return array(
    'name' => 'Helpim Интеграция',
    'description' => 'Автоматизация интернет-продаж, интеграция с платформой Helpim.',
    'img' => 'img/1616.png',
    'version' => '0.2.0.0',
    'vendor' => 1050212,
    'shop_settings' => true,
    'handlers' => array(
        'backend_order' => 'backendOrder',
        'backend_order_edit' => 'backendOrderEdit',
        'order_action.create' => 'order',
        'order_action.process' => 'order',
        'order_action.pay' => 'order',
        'order_action.ship' => 'order',
        'order_action.refund' => 'order',
        'order_action.edit' => 'order',
        'order_action.delete' => 'order',
        'order_action.restore' => 'order',
        'order_action.complete' => 'order',
        'order_action.comment' => 'order',
        'order_action.message' => 'order',
    ),
);
