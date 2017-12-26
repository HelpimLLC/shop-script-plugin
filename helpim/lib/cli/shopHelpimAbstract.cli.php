<?php

abstract class shopHelpimAbstractCli extends waCliController
{
    private $helpim;
    private $shopHelpimLogsModel;

    protected function helpim()
    {
        if ($this->helpim) {
            return $this->helpim;
        }

        $appConfig = wa()->getConfig()->getAppConfig('shop');

        return $this->helpim = new shopHelpimProxy();
    }

    protected function addLog($action, $statusCode, $message)
    {
        if (!$this->shopHelpimLogsModel) {
            $this->shopHelpimLogsModel = new shopHelpimLogsModel();
        }

        $this->shopHelpimLogsModel->addLog($action, $statusCode, $message);
    }
}
