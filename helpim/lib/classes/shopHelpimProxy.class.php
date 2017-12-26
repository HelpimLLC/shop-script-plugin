<?php
/**
 * PHP version 5.2
 *
 * Wrapper
 *
 * @category helpim
 * @package  api-client-php
 * @author   Helpim <it@help-im.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://help-im.ru
 */

class shopHelpimProxy
{
    const LOG_FILE = 'shop/plugins/helpim.log';

    private $api;

    public function __construct()
    {
        $pluginId = array('shop', 'helpim');
        $waAppSettingsModel = new waAppSettingsModel();
        $customerServiceId = (int) $waAppSettingsModel->get($pluginId, 'customer_service_id');
        $token = $waAppSettingsModel->get($pluginId, 'token');

        if (!$customerServiceId || !$token) {
            $error = 'Не заданы Идентификатор сервиса и/или API-токен во вкладке "Параметры"';
            self::errorLog('[proxy_init] ' . $error);
            throw new waException($error);
        }

        $this->api = new shopHelpimHttpClient(array(
            'customerServiceId' => $customerServiceId,
            'token' => $token,
        ));
    }

    /**
     * Make simple request to Helpim API
     *
     * The method name is used as a name of structure which should be sent.
     *
     * @param string $method    Method name
     * @param array  $arguments Method arguments
     *
     * @return shopHelpimClientResponse
     */
    public function __call($method, array $arguments)
    {
        $response = $this->_request(array($method => $arguments[0]));

        if (!$response->isSuccessful()) {
            self::errorLog(sprintf('[%s] %s (%s)', $method, $response->getError(), $response->getMessage()));
            throw new waException($response->getError() . ': ' . $response->getMessage(), $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Make request to Helpim API
     *
     * @param array $data An array of data
     *
     * @return shopHelpimClientResponse
     *
     * @throws shopHelpimCurlException
     * @throws shopHelpimInvalidJsonException
     */
    private function _request(array $data)
    {
        $retry = 3;
        $pause = 1;

        while ($retry--) {
            try {
                return $this->api->request($data);
            } catch (shopHelpimCurlException $e) {
                self::errorLog('cURL error: ' . $e->getMessage() . ($retry > 0 ? '. retries left: ' . $retry : ''));
            } catch (shopHelpimInvalidJsonException $e) {
                self::errorLog('JSON error: ' . $e->getMessage() . ' in "' . $e->getSourceString() . '"');
                break;
            }

            sleep($pause);
        }

        throw new waException('Ошибка. Не удалось выполнить запрос к Helpim');
    }

    private static function errorLog($message) {
        waLog::log($message, self::LOG_FILE);
    }

    /**
     * Make custom request to Helpim API
     *
     * @param array $data An array of data
     *
     * @return shopHelpimClientResponse
     */
    public function request(array $data)
    {
        $response = $this->_request($data);

        if (!$response->isSuccessful()) {
            self::errorLog(sprintf('[_custom_] %s (%s)', $response->getError(), $response->getMessage()));
            throw new waException($response->getError() . ': ' . $response->getMessage(), $response->getStatusCode());
        }

        return $response;
    }
}
