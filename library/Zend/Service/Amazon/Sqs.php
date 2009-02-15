<?php

require_once 'Zend/Service/Abstract.php';

require_once 'Zend/Service/Amazon/Sqs/Response.php';

abstract class Zend_Service_Amazon_Sqs extends Zend_Service_Amazon_Abstract
{
    /**
     * The HTTP query server
     */
    const SQS_ENDPOINT = 'queue.amazonaws.com';

    /**
     * The API version to use
     */
    const SQS_API_VERSION = '2008-01-01';

    /**
     * Legacy parameter required by SQS
     */
    const SQS_SIGNATURE_VERSION = '1';

    /**
     * Period after which HTTP request will timeout in seconds
     */
    const HTTP_TIMEOUT = 10;

    /**
     * Make a request and return the response
     *
     * @param array $params
     * @param string $queueUrl
     * @return Zend_Http_Response
     */
    protected function sendRequest(array $params = array(), $queueUrl = null)
    {
        $url = ($queueUrl) ? $queueUrl : 'http://' . self::SQS_ENDPOINT . '/';

        $params = $this->addRequiredParameters($params);

        try {
            /* @var $request Zend_Http_Client */
            $request = self::getHttpClient();

            $request->setConfig(array(
                'timeout' => self::HTTP_TIMEOUT
            ));

            $request->setUri($url);
            $request->setMethod(Zend_Http_Client::POST);
            $request->setParameterPost($params);

            $httpResponse = $request->request();


        } catch (Zend_Http_Client_Exception $zhce) {
            $message = 'Error in request to AWS service: ' . $zhce->getMessage();
            throw new Zend_Service_Amazon_Sqs_Exception($message, $zhce->getCode());
        }

        $response = new Zend_Service_Amazon_Sqs_Response($httpResponse);

        $this->_checkForErrors($response);

        return $response;
    }


    protected function isValidVisibiltyTimeout($timeout)
    {
        $valid = true;

        if($timeout < 0 || $timeout > 7200) {
            $valid = false;
        }

        return $valid;
    }

    protected function addRequiredParameters(array $parameters)
    {
        $parameters['AWSAccessKeyId']   = $this->_accessKey;
        $parameters['SignatureVersion'] = self::SQS_SIGNATURE_VERSION;
        $parameters['Timestamp']        = $this->_getFormattedTimestamp();
        $parameters['Version']          = self::SQS_API_VERSION;
        $parameters['Signature']        = $this->signParameters($parameters);

        return $parameters;
    }

    protected function signParameters(array $paramaters)
    {
        $data = '';

        uksort($paramaters, 'strcasecmp');
        unset($paramaters['Signature']);

        foreach($paramaters as $key => $value) {
            $data .= $key . $value;
        }

        return $this->_sign($data);
    }

    private function _sign($data)
    {
        require_once 'Zend/Crypt/Hmac.php';

        $hmac = Zend_Crypt_Hmac::compute($this->_secretKey, 'SHA1', $data, Zend_Crypt_Hmac::BINARY);

        return base64_encode($hmac);
    }

    private function _getFormattedTimestamp()
    {
        return gmdate('c');
    }

    private function _checkForErrors(Zend_Service_Amazon_Sqs_Response $response)
    {
        $xpath = $response->getXPath();
        $list  = $xpath->query('//sqs:Error');
        if ($list->length > 0) {
            $node    = $list->item(0);
            $code    = $xpath->evaluate('string(sqs:Code/text())', $node);
            $message = $xpath->evaluate('string(sqs:Message/text())', $node);
            throw new Zend_Service_Amazon_Sqs_Exception($message, 0, $code);
        }

    }


}