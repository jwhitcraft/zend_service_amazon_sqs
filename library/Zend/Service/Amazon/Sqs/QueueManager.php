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

/* Zend_Service_Amazon_Sqs */
require_once '/Zend/Service/Amazon/Sqs.php';

/* Zend_Service_Amazon_Sqs_Exception */
require_once 'Zend/Service/Amazon/Sqs/Exception.php';

/**
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Sqs
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Amazon_Sqs_QueueManager extends Zend_Service_Amazon_Sqs
{
    /**
     * Gets a list of SQS queues for the current account
     *
     * @param string $prefix optional. Only list queues whose name begins with
     *                       the given prefix. If not specified, all queues for
     *                       the account are returned.
     *
     * @return array an array of {@link Zend_Service_Amazon_Sqs_Queue} objects.
     *
     * @throws Zend_Service_Amazon_Sqs_Exception if one or more errors are
     *         returned by Amazon.
     *
     * @throws Zend_Http_Client_Exception if the HTTP request fails.
     */
    public function listQueues($prefix = null)
    {
        $params = array();

        $params['Action'] = 'ListQueues';

        if ($prefix) {
            $params['QueueNamePrefix'] = $prefix;
        }

        $response = $this->sendRequest($params);

        $queues = array();
        $xpath  = $response->getXPath();
        $nodes  = $xpath->query('//sqs:QueueUrl');

        foreach ($nodes as $node) {
            $url   = $xpath->evaluate('string(text())', $node);
            $queue = new Services_Amazon_SQS_Queue($url, $this->account, '', $this->request);

            $queues[] = $queue;
        }

        return $queues;
    }

    /**
     * Creates a new queue for the current account
     *
     * @param string  $name    the queue name.
     * @param integer $timeout optional. Timeout for message visibility
     *
     * @return Zend_Service_Amazon_Sqs_Exception if one or more errors are
     *         returned by Amazon.
     *
     * @throws Zend_Http_Client_Exception if the HTTP request fails.
     */
    public function addQueue($name, $timeout = null)
    {
        if(!$this->isValidQueueName($name)) {
            throw new Zend_Service_Amazon_Sqs_Exception('The queue ' .
                'name "' . $name . '" is not a valid queue name. Queue names ' .
                'must be 1-80 characetrs long and must consist only of ' .
                'alphanumeric characters, dashes or underscores.');
        }

        $params = array();


        $params['Action']    = 'CreateQueue';
        $params['QueueName'] = $name;

        $params['DefaultVisibilityTimeout'] =
            ($timeout !== null) ? $timeout : 30;

        try {
            $response = $this->sendRequest($params);
        } catch (Zend_Service_Amazon_Sqs_Exception $e) {
            switch ($e->getCode()) {
            case 'AWS.SimpleQueueService.QueueDeletedRecently':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'queue "' . $name . '" was deleted recently. Please wait ' .
                    '60 seconds after deleting a queue before creating a ' .
                    'queue of the same name.');

            case 'AWS.SimpleQueueService.QueueNameExists':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'queue "' . $name . '" already exists. To set a ' .
                    'different visibility timeout, use the ' .
                    'Zend_Service_Amazon_Sqs_Queue::setAttribute() method.');

            default:
                throw $e;
            }
        }

        $xpath    = $response->getXPath();
        $queueUrl = $xpath->evaluate('string(//sqs:QueueUrl/text())');

        $queue = new Zend_Service_Amazon_Sqs_Queue($queueUrl, $this->account, '',
            $this->request);

        return $queue;
    }

    /**
     * Deletes a queue
     *
     * All existing messages in the queue will be lost.
     *
     * @param Zend_Service_Amazon_Sqs_Queue|string $queue either a queue object or
     *                                                the queue URL of the
     *                                                queue to be deleted.
     *
     * @return void
     *
     * @throws Zend_Service_Amazon_Sqs_Exception if one or more errors are
     *         returned by Amazon.
     *
     * @throws Zend_Http_Client_Exception if the HTTP request fails.
     */
    public function deleteQueue($queue)
    {
        if($queue instanceof Zend_Service_Amazon_Sqs_Queue) {
            $queue = strval($queue);
        }

        $params = array();

        $params['Action'] = 'DeleteQueue';

        $this->sendRequest($params, $queue);
    }

    /**
     * Gets whether or not a queue name is valid for Amazon SQS
     *
     * Amazon SQS queue names must conform to the following rules:
     * - must be 1 to 80 ASCII characters
     * - must contain only alphanumeric characters, dashes (-), and
     *   underscores (_).
     *
     * @param string $name the queue name to check.
     *
     * @return boolean true if the provided queue name is a valid SQS queue
     *                 name, otherwise false.
     */
    protected function isValidQueueName($name)
    {
        $valid = true;

        if (preg_match('/^[A-Za-z0-9\-\_]{1,80}$/', $name) === 0) {
            $valid = false;
        }

        return $valid;
    }
}