<?php

class shopHelpimFullExportCli extends shopHelpimAbstractCli
{
    public function execute()
    {
        $start = new DateTime();
        $orderModel = new shopOrderModel();
        $options = array(
            'limit' => 10,
            'offset' => 0,
        );

        while ($orderList = $orderModel->getList('id', $options)) {
            print_r($options);
            $orderSendList = array();
            foreach($orderList as $order) {
                var_dump($order['id']);
                $orderSendList[] = $orderModel->getOrder($order['id'], true);
            }
            $response = $this->helpim()->orders($orderSendList);
            $options['offset'] += $options['limit'];
        }

        $lasts = $start->diff(new DateTime());

        $this->addLog('full_export', $response->getStatusCode(), 'Полный экспорт выполнен за ' . $lasts->format('%dd%H:%I:%S'));
    }
}

