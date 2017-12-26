<?php
/**
 * PHP version 5.2
 *
 * Invalid JSON Exception
 *
 * @category helpim
 * @package  api-client-php
 * @author   Helpim <it@help-im.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://help-im.ru
 */

class shopHelpimInvalidJsonException extends RuntimeException {
    private $sourceString;

    public function __construct($message = '', $code = 0, $sourceString = '')
    {
        parent::__construct($message, $code);
        $this->sourceString = $sourceString;
    }

    public function getSourceString()
    {
        return $this->sourceString;
    }
}

