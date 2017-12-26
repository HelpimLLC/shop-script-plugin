<?php

class shopHelpimPlugin extends shopPlugin
{
    const SHIPPING_PLUGIN_CODE = 'helpim';

    private $helpim;
    private $shopHelpimLogsModel;

    private static $helpimShippingPluginId;
    private static $stockActions = array(
        'complete',
        'pay',
        'process',
    );

    protected function addLog($action, $statusCode, $message)
    {
        if (!$this->shopHelpimLogsModel) {
            $this->shopHelpimLogsModel = new shopHelpimLogsModel();
        }

        $this->shopHelpimLogsModel->addLog($action, $statusCode, $message);
    }

    public function backendOrder(array $order)
    {
        $dom = new DOMDocument();
        $script = $dom->createElement('script');
        $script->setAttribute('src', '../../wa-apps/shop/plugins/helpim/js/order.js');
        $dom->appendChild($script);

        return array(
            'info_section' => $dom->saveHTML(),
        );
    }

    public function backendOrderEdit(array $order)
    {
        if (!$shippingPluginId = self::getHelpimShippingPluginId()) {
            return '';
        }

        $dom = new DOMDocument();

        $table = $dom->createElement('table');
        $dom->appendChild($table);

        $tr = $dom->createElement('tr');
        $tr->setAttribute('id', 'helpim_shipping');
        $table->appendChild($tr);

        $td = $dom->createElement('td');
        $td->setAttribute('class', 'align-right white');
        $td->setAttribute('colspan', '4');
        $tr->appendChild($td);

        /* workaround to pass SS6 bug: it checks for 'shipping##' instead of 'shipping_##' */
        $input = $dom->createElement('input');
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('name', 'shipping' . $shippingPluginId);
        $input->setAttribute('value', 1);
        $td->appendChild($input);

        $div = $dom->createElement('div');
        $div->appendChild($dom->createTextNode('Желаемая дата доставки '));
        $input = $dom->createElement('input');
        $input->setAttribute('type', 'date');
        $input->setAttribute('id', 'helpim_delivery_date');
        $input->setAttribute('name', 'shipping_' . $shippingPluginId . '[delivery_date]');
        $input->setAttribute('value', @$order['params']['shipping_params_delivery_date']);
        $div->appendChild($input);
        $td->appendChild($div);

        $div = $dom->createElement('div');
        $div->appendChild($dom->createTextNode('Желаемое время доставки '));

        $input = $dom->createElement('input');
        $input->setAttribute('type', 'time');
        $input->setAttribute('id', 'helpim_delivery_time_from');
        $input->setAttribute('name', 'shipping_' . $shippingPluginId . '[delivery_time_from]');
        $input->setAttribute('value', @$order['params']['shipping_params_delivery_time_from']);
        $div->appendChild($input);

        $div->appendChild($dom->createTextNode(' - '));

        $input = $dom->createElement('input');
        $input->setAttribute('type', 'time');
        $input->setAttribute('id', 'helpim_delivery_time_to');
        $input->setAttribute('name', 'shipping_' . $shippingPluginId . '[delivery_time_to]');
        $input->setAttribute('value', @$order['params']['shipping_params_delivery_time_to']);
        $div->appendChild($input);

        $td->appendChild($div);

        $script = $dom->createElement('script');
        $script->setAttribute('src', '../../wa-apps/shop/plugins/helpim/js/order_edit.js');

        $dom->appendChild($script);

        return $dom->saveHTML();
    }

    private function helpim()
    {
        if ($this->helpim) {
            return $this->helpim;
        }

        return $this->helpim = new shopHelpimProxy();
    }

    private static function getHelpimShippingPluginId()
    {
        if (isset(self::$helpimShippingPluginId)) {
            return self::$helpimShippingPluginId;
        }

        foreach (shopHelper::getShippingMethods() as $method) {
            if ($method['plugin'] == self::SHIPPING_PLUGIN_CODE && $method['available']) {
                self::$helpimShippingPluginId = (int) $method['id'];
            }
        }

        return self::$helpimShippingPluginId;
    }

    /**
     * Get shipment address from Helpim Shipping plugin (if installed)
     */
    private static function getShipmentAddress()
    {
        if ($shippingPluginId = self::getHelpimShippingPluginId()) {
            $address = array();
            foreach (shopShipping::getPlugin(self::SHIPPING_PLUGIN_CODE, $shippingPluginId)->getSettings()
                as $key => $value)
            {
                if (strpos($key, 'shipment_address_') === 0) {
                    $address[$key] = $value;
                }
            }

            return $address;
        }

        return null;
    }

    public function order($args)
    {
        try {
            $shopOrderModel = new shopOrderModel();
            if (!$order = $shopOrderModel->getOrder($args['order_id'])) {
                throw new UnexpectedValueException('Заказ не найден: ' .
                    shopHelper::encodeOrderId($args['order_id']));
            }

            $order['number'] = shopHelper::encodeOrderId($order['id']);

            $order['prepaid'] = 0;
            if (!empty($order['paid_date'])) {
                $order['prepaid'] = $order['total'];
            }

            /* convert items from assoc array to index array */
            $order['items'] = array_values($order['items']);

            /* count total order items price and weight, set sku_code for services */
            $order['base_price'] = 0;
            $order['weight'] = null;

            $shopFeatureModel = new shopFeatureModel();
            $feature = $shopFeatureModel->getByCode('weight');
            if ($feature) {
                $shopFeatureValuesModel = $shopFeatureModel->getValuesModel($feature['type']);
            }

            foreach ($order['items'] as &$item) {
                if (isset($shopFeatureValuesModel)) {
                    $features = $shopFeatureValuesModel->getProductValues($item['product_id'], $feature['id']);
                    if (isset($features['skus'][$item['sku_id']])) {
                        $item['weight'] = $features['skus'][$item['sku_id']];
                        $order['weight'] += $item['weight'] * $item['quantity'];
                    }
                }

                $order['base_price'] += @$item['price'] * $item['quantity'];

                if ($item['type'] == 'service') {
                    $item['sku_code'] = 'service_' . (int) @$item['service_variant_id'];
                }
            }

            /* set default weight */
            /* TODO: load default weight from plugin settings */
            if (!$order['weight']) {
                $order['weight'] = 1000;
            }

            /* set delivery fields */
            if (!empty($order['params']['shipping_plugin'])) {
                if ($order['params']['shipping_plugin'] == 'helpim') {
                    $order['params']['shipping_params_delivery_integration_code'] = 'helpim';
                    $tariff = explode('.', $order['params']['shipping_rate_id'], 2);
                    $order['params']['shipping_params_delivery_service'] = $tariff[0];
                    if (isset($tariff[1])) {
                        $order['params']['shipping_params_delivery_point'] = $tariff[1];
                    }
                } else {
                    $order['params']['shipping_params_delivery_service'] = $order['params']['shipping_plugin'];
                    $order['params']['shipping_params_delivery_date'] =
                        ifempty($order['params']['shipping_params_delivery_date'],
                            self::extractFromDateTime(@$order['shipping_datetime'], 'Y-m-d'));
                    $order['params']['shipping_params_delivery_time_from'] =
                        ifempty($order['params']['shipping_params_delivery_time_from'],
                            self::extractFromDateTime(@$order['params']['shipping_start_datetime'], 'H:i'));
                    $order['params']['shipping_params_delivery_time_to'] =
                        ifempty($order['params']['shipping_params_delivery_time_to'],
                            self::extractFromDateTime(@$order['params']['shipping_end_datetime'], 'H:i'));
                }
            }

            $order['params'] = array_merge($order['params'], self::getShipmentAddress());

            $shopOrderLogModel = new shopOrderLogModel();
            $order['log'] = $shopOrderLogModel->getLog($order['id']);

            $response = $this->helpim()->orders(array($order));
            $this->addLog('order_export', $response->getStatusCode(),
                'Заказ экспортирован: ' . $order['number']);

            $waAppSettingsModel = new waAppSettingsModel();
            $shopSettings = $waAppSettingsModel->get('shop');

            /* send stock update */
            if (isset($args['action_id']) &&
                ((!empty($shopSettings['update_stock_count_on_create_order']) &&
                $args['action_id'] == 'create') ||
                in_array($args['action_id'], self::$stockActions))
            ) {
                $this->stock($order['items']);
            }
        } catch (Exception $e) {
            $this->addLog('order_export', $e->getCode(), $e->getMessage());
            throw $e;
        }
    }

    private static function extractFromDateTime($dateTimeString, $format = 'Y-m-d')
    {
        if (empty($dateTimeString)) {
            return null;
        }

        $dateTime = new DateTime($dateTimeString);
        return $dateTime->format($format);
    }

    public function stock($items)
    {
        $shopProductSkusModel = new shopProductSkusModel();

        $stocks = array();
        foreach($items as $item) {
            if ($item['type'] != 'product') {
                continue;
            }

            if ($stock = $shopProductSkusModel->getSku($item['item']['sku_id'])) {
                $stocks[] = $stock;
            }
        }

        if (!count($stocks)) {
            return;
        }

        try {
            $response = $this->helpim()->stock($stocks);
            $this->addLog('stock_export', $response->getStatusCode(), 'Остатки экспортированы');
        } catch (Exception $e) {
            $this->addLog('stock_export', $e->getCode(), $e->getMessage());
        }
    }
}
