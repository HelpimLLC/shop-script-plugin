<?php

class shopHelpimPluginSettingsGetlogController extends shopHelpimPluginSettingsAbstractController
{
    public function execute()
    {
        try {
            $limit = waRequest::get('limit') ? (int) waRequest::get('limit') : 20;
            $offset = waRequest::get('offset') ? (int) waRequest::get('offset') : 0;
            $shopHelpimLogsModel = new shopHelpimLogsModel();
            $this->response['total'] = $shopHelpimLogsModel->countAll();
            $this->response['logs'] = $shopHelpimLogsModel->getLogs($limit, $offset);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
