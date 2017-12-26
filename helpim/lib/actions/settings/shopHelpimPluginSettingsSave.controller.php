<?php

class shopHelpimPluginSettingsSaveController extends shopHelpimPluginSettingsAbstractController {

    public function execute()
    {
        $pluginId = array('shop', 'helpim');

        try {
            $waAppSettingsModel = new waAppSettingsModel();
            $settings = waRequest::post('settings');

            $waAppSettingsModel->set($pluginId, 'customer_service_id', $settings['customer_service_id']);
            $waAppSettingsModel->set($pluginId, 'token', $settings['token']);

            if (!$waAppSettingsModel->get($pluginId, 'installed')) {
                $this->sendStatesToHelpim();
                $waAppSettingsModel->set($pluginId, 'installed', 1);
            }

            $this->response['message'] = 'Сохранено';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    private function sendStatesToHelpim() {
        $workflow = new shopWorkflow();
        try {
            $response = $this->helpim()->statuses($workflow->getAllStates());
            $this->addLog('states_export', $response->getStatusCode(), 'Статусы экспортированы');
        } catch (Exception $e) {
            $this->addLog('states_export', $e->getCode(), $e->getMessage());
            throw $e;
        }
    }
}

