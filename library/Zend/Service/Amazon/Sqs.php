<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Sqs
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 8064 2008-02-16 10:58:39Z thomas $
 */

require_once 'Zend/Service/Amazon/Abstract.php';

require_once 'Zend/Service/Amazon/Sqs/Response.php';

/**
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Sqs
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
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
     * Sends a HTTP request to the queue service using Zend_Http_Client
     *
     * The supplied <tt>$params</tt> array should contain only the specific
     * parameters for the request type and should not include account,
     * signature, or timestamp related parameters. These parameters are added
     * automatically.
     *
     * @param array  $params   optional. Array of request parameters for the
     *                         API call.
     * @param string $queueUrl optional. The specific queue URL for which
     *                         the request is made. Does not need to be
     *                         specified for general actions like listing
     *                         queues.
     *
     * @return mixed Zend_Service_Amazon_Sqs_Response object or false if the
     *               request failed.
     *
     * @throws Zend_Service_Amazon_Sqs_Exception if one or more errors are
     *         returned from Amazon.
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

        $this->checkForErrors($response);

        return $response;
    }

    /**
     * Gets whether or not a visibility timeout is valid
     *
     * Visibility timeouts must be between 0 and 7200 seconds.
     *
     * @param integer $timeout the timeout value to check (in seconds).
     *
     * @return boolean true if the timeout is valid, otherwise false.
     */
    protected function isValidVisibiltyTimeout($timeout)
    {
        $valid = true;

        if($timeout < 0 || $timeout > 7200) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * Adds required authentication and version parameters to an array of
     * parameters
     *
     * The required parameters are:
     * - AWSAccessKey
     * - SignatureVersion
     * - Timestamp
     * - Version and
     * - Signature
     *
     * If a required parameter is already set in the <tt>$parameters</tt> array,
     * it is overwritten.
     *
     * @param array $parameters the array to which to add the required
     *                          parameters.
     *
     * @return array
     */
    protected function addRequiredParameters(array $parameters)
    {
        $parameters['AWSAccessKeyId']   = $this->getAccessKey();
        $parameters['SignatureVersion'] = self::SQS_SIGNATURE_VERSION;
        $parameters['Timestamp']        = gmdate('c');
        $parameters['Version']          = self::SQS_API_VERSION;
        $parameters['Signature']        = $this->signParameters($parameters);

        return $parameters;
    }

    /**
     * Computes the RFC 2104-compliant HMAC signature for request parameters
     *
     * This implements the Amazon Web Services signature, as per the following
     * specification:
     *
     * 1. Sort all request parameters (including <tt>SignatureVersion</tt> and
     *    excluding <tt>Signature</tt>, the value of which is being created),
     *    ignoring case.
     *
     * 2. Iterate over the sorted list and append the parameter name (in its
     *    original case) and then its value. Do not URL-encode the parameter
     *    values before constructing this string. Do not use any separator
     *    characters when appending strings.
     *
     * @param array  $parameters the parameters for which to get the signature.
     * @param string $secretKey  the secret key to use to sign the parameters.
     *
     * @return string the signed data.
     */
    protected function signParameters(array $paramaters)
    {
        $data = '';

        uksort($paramaters, 'strcasecmp');
        unset($paramaters['Signature']);

        foreach($paramaters as $key => $value) {
            $data .= $key . $value;
        }

        require_once 'Zend/Crypt/Hmac.php';
        $hmac = Zend_Crypt_Hmac::compute($this->getSecretKey(), 'SHA1', $data, Zend_Crypt_Hmac::BINARY);

        return base64_encode($hmac);
    }

    /**
     * Checks for errors responses from Amazon
     *
     * @param Zend_Service_Amazon_Sqs_Response $response the response object to
     *                                                   check.
     *
     * @return void
     *
     * @throws Zend_Service_Amazon_Sqs_Exception if one or more errors are
     *         returned from Amazon.
     */
    private function checkForErrors(Zend_Service_Amazon_Sqs_Response $response)
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