<?php

class shopHelpimPluginSettingsExportstatesController extends shopHelpimPluginSettingsAbstractController
{
    public function execute()
    {
        try {
            $workflow = new shopWorkflow();
            $response = $this->helpim()->statuses($workflow->getAllStates());

            $this->response['message'] = 'Статусы экспортированы';
            $this->addLog('states_export', $response->getStatusCode(), $this->response['message']);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            $this->addLog('states_export', $e->getCode(), $e->getMessage());
        }
    }
}
