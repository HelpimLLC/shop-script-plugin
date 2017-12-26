<?php

class shopHelpimPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $this->view->assign('max_execution_time', ini_get('max_execution_time'));

        $waAppSettingsModel = new waAppSettingsModel();
        $this->view->assign('settings', $waAppSettingsModel->get(array('shop', 'helpim')));
        $this->view->assign('settings_wa', $waAppSettingsModel->get('webasyst'));
        $this->view->assign('wa_path_root', waConfig::get('wa_path_root'));

        $shopPluginModel = new shopPluginModel();
        $delivery = $shopPluginModel->listPlugins('shipping', array('status' => 1));
        $payment = $shopPluginModel->listPlugins('payment', array('status' => 1));

        usort($delivery, array($this, 'sortByName'));
        usort($payment, array($this, 'sortByName'));

        $this->view->assign('plugins_delivery', $delivery);
        $this->view->assign('plugins_payment', $payment);
    }

    private function sortByName($a, $b)
    {
        if ($a['name'] == $b['name']) return 0;
        return ($a['name'] > $b['name']) ? 1 : -1;
    }
}
