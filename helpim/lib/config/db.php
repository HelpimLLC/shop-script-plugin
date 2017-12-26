<?php

return array(
    'shop_helpim_logs' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'datetime' => array('datetime', 'null' => 0),
        'action' => array('varchar', 255, 'null' => 1),
        'code' => array('int', 3, 'null' => 0),
        'message' => array('text', 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
            'datetime' => array('datetime'),
        ),
    ),
);
