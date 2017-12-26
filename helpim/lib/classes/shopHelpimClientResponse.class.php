<?php
/**
 * PHP version 5.2
 *
 * Response from Helpim API
 *
 * @category helpim
 * @package  api-client-php
 * @author   Helpim <it@help-im.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://help-im.ru
 */

class shopHelpimClientResponse implements ArrayAccess
{
    /* HTTP response status code */
    protected $statusCode;

    /* Response assoc array */
    protected $response;

    /**
     * ApiResponse constructor.
     *
     * @param int   $statusCode   HTTP status code
     * @param mixed $responseBody Response body
     *
     * @throws shopHelpimInvalidJsonException
     */
    public function __construct($statusCode, $responseBody = null)
    {
        $this->statusCode = (int) $statusCode;

        if (!empty($responseBody)) {
            $response = json_decode($responseBody, true);

            if ($response === null) {
                throw new shopHelpimInvalidJsonException('Invalid JSON in the API response body', 0, $responseBody);
            }

            $this->response = $response;
        }
    }

    /**
     * Return HTTP response status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * HTTP request was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return (bool) ($this->statusCode < 400);
    }

    /**
     * Access to the property throw class method
     *
     * @param string $name      method name
     * @param mixed  $arguments method parameters
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        /* convert getSomeProperty to someProperty */
        $propertyName = strtolower(substr($name, 3, 1)) . substr($name, 4);

        if (!isset($this->response[$propertyName])) {
            return null;
        }

        return $this->response[$propertyName];
    }

    /**
     * Access to the property throw object property
     *
     * @param string $name property name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->response[$name])) {
            return null;
        }

        return $this->response[$name];
    }

    /**
     * Offset set
     *
     * @param mixed $offset offset
     * @param mixed $value  value
     *
     * @throws BadMethodCallException
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Method is not allowed');
    }

    /**
     * Offset unset
     *
     * @param mixed $offset offset
     *
     * @throws BadMethodCallException
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Method is not allowed');
    }

    /**
     * Check offset
     *
     * @param mixed $offset offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->response[$offset]);
    }

    /**
     * Get offset
     *
     * @param mixed $offset offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }
}
