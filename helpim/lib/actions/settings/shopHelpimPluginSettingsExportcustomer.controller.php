<?php

class shopHelpimPluginSettingsExportcustomerController extends shopHelpimPluginSettingsAbstractController
{
    public function execute()
    {
        try {
            $this->sendToHelpim(waRequest::get('id'));
            $this->response['message'] = 'Покупатель экспортирован';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

    private function sendToHelpim($customerId) {
        $shopCustomerModel = new shopCustomerModel();
        $customer = $shopCustomerModel->getById($customerId);

        if (empty($customer)) {
            throw new UnexpectedValueException('Покупатель не найден: ' . $customerId);
        }

        $contact = new waContact($customer['contact_id']);
        $customer['contact'] = $contact->load();

        try {
            $response = $this->helpim()->customers(array($customer));
            $this->addLog('customer_export', $response->getStatusCode(), 'Покупатель экспортирован: ' . $customerId);
        } catch (Exception $e) {
            $this->addLog('customer_export', $e->getCode(), $e->getMessage());
            throw $e;
        }
    }
}

