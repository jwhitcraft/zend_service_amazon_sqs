<?php

require_once 'Zend/Service/Abstract.php';

abstract class Zend_Service_Amazon_Abstract extends Zend_Service_Abstract
{
    /**
     * @var string Amazon Access Key
     */
    protected static $default_accessKey = null;

    /**
     * @var string Amazon Secret Key
     */
    protected static $default_secretKey = null;

    /**
     * @var string Amazon Secret Key
     */
    protected $_secretKey;

    /**
     * @var string Amazon Access Key
     */
    protected $_accessKey;

    /**
     * Set the keys to use when accessing SQS.
     *
     * @param  string $access_key
     * @param  string $secret_key
     * @return void
     */
    public static function setKeys($access_key, $secret_key)
    {
        self::$default_accessKey = $access_key;
        self::$default_secretKey = $secret_key;
    }

    /**
     * Create Amazon Sqs client.
     *
     * @param  string $access_key
     * @param  string $secret_key
     * @return void
     */
    public function __construct($access_key=null, $secret_key=null)
    {
        if(!$access_key) {
            $access_key = self::$default_accessKey;
        }
        if(!$secret_key) {
            $secret_key = self::$default_secretKey;
        }
        if(!$access_key || !$secret_key) {
            require_once 'Zend/Service/Amazon/Exception.php';
            throw new Zend_Service_Amazon_Exception("AWS keys were not supplied");
        }
        $this->_accessKey = $access_key;
        $this->_secretKey = $secret_key;
    }

    /**
     * Method to fetch the Access Key
     *
     * @return string
     */
    protected function getAccessKey()
    {
        return $this->_accessKey;
    }

    /**
     * Method to fetch the Secret AWS Key
     *
     * @return string
     */
    protected function getSecretKey()
    {
        return $this->_secretKey;
    }
}