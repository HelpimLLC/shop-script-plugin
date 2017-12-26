<?php

$pluginId = array('shop', 'helpim');
$waAppSettingsModel = new waAppSettingsModel();
$waAppSettingsModel->set($pluginId, 'path_yml', 'wa-data/public/shop/helpim/product.xml');
