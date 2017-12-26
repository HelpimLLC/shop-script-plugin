<?php

class shopHelpimPluginSettingsGenerateymlController extends waJsonController
{
    public function execute()
    {
        try {
            $shopHelpimGenerateYmlCli = new shopHelpimGenerateYmlCli();
            $shopHelpimGenerateYmlCli->execute();
            $this->response['message'] = 'Файл экспорта каталога сгенерирован';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            $this->addLog('catalog_export', $e->getCode(), $e->getMessage());
        }
    }
}

