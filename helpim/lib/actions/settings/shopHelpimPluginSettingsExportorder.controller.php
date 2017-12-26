<?php

class shopHelpimPluginSettingsExportorderController extends shopHelpimPluginSettingsAbstractController
{
    public function execute()
    {
        try {
            $shopHelpimPlugin = new shopHelpimPlugin(wa()->getConfig()->getAppConfig('shop')->getPluginInfo('helpim'));
            $shopHelpimPlugin->order(array('order_id' => shopHelper::decodeOrderId(waRequest::get('id'))));
            $this->response['message'] = 'Заказ экспортирован';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
