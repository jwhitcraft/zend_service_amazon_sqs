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
require_once 'Zend/Service/Amazon/Sqs.php';

/* Zend_Service_Amazon_Sqs_Exception */
require_once 'Zend/Service/Amazon/Sqs/Exception.php';

/**
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Sqs
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Amazon_Sqs_Queue extends Zend_Service_Amazon_Sqs
{
    /**
     * The url of the queue we wanting to listen to or send message to
     *
     * @var string
     */
    protected $queueUrl = null;

    /**
     * Creates a PHP SQS queue object
     *
     * Queue objects are created with the full URL because Amazon reserves the
     * right to change the URL scheme for queues created in the future.
     *
     * @param string $queueUrl the URL of this queue.
     *
     * @param string $accessKey either a string containing the SQS access key for an account.
     *
     * @param string $secretKey the secret access key for the SQS account.
     *
     */
    public function __construct($queueUrl, $accessKey = null, $secretKey = null)
    {
        // Make sure the queue url contains the sqs end point;
        if(false === strpos($queueUrl, self::SQS_ENDPOINT)) {
            $queueUrl = 'http://' . self::SQS_ENDPOINT . '/' . $queueUrl;
        }
        $this->queueUrl = $queueUrl;

        // start the parent class up.
        parent::__construct($accessKey, $secretKey);
    }

    /**
     * Gets a string representation of this queue
     *
     * Specifically, this returns the queue URL of this queue.
     *
     * @return string the URL of this queue.
     */
    public function __toString()
    {
        return $this->queueUrl;
    }

    /**
     * Sends a message to this queue
     *
     * @param string $message the message to put in this queue.
     *
     * @return string the message id of the message.
     *
     * @throws Zend_Service_Amazon_Sqs_Exception
     */
    public function send($message)
    {
        $params = array();

        $params['Action'] = 'SendMessage';
        $params['MessageBody'] = $message;

        try {
            $response = $this->sendRequest($params, $this->queueUrl);
        } catch (Zend_Service_Amazon_Sqs_Exception $e) {
            switch ($e->getErrorCode()) {
            case 'InvalidMessageContents':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'message contains characters outside the allowed set.', 0,
                    $message);

            case 'MessageTooLong':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'message size can not exceed 8192 bytes.', 0, $message);

            case 'AWS.SimpleQueueService.NonExistentQueue':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'queue "' . $this . '" does not exist.', 0,
                    $this->queueUrl);

            default:
                throw $e;
            }
        }

        $xpath        = $response->getXPath();
        $expectedMd5  = md5($message);

        $id  = $xpath->evaluate('string(//sqs:MessageId/text())');
        $md5 = $xpath->evaluate('string(//sqs:MD5OfMessageBody/text())');

        if ($md5 !== $expectedMd5) {
            throw new Zend_Service_Amazon_Sqs_Exception('Message ' .
                'body was not received by Amazon correctly. Expected ' .
                'MD5 was: "' . $expectedMd5 . '", but received MD5 was: "' .
                $md5 .'".', 0, $id);
        }

        return $id;
    }

    /**
     * Retrieves one or more messages from this queue
     *
     * Retrieved messages are made invisible to subsequent requests for the
     * duration of the visibility timeout. To permanently remove a message from
     * this queue, first retrieve the message and them delete it using the
     * {@link Zend_Service_Amazon_Sqs_Queue::delete()} method.
     *
     * @var integer $count     optional. The number of messages to retrieve from
     *                         the queue. If not specified, one message is
     *                         retrieved. At most, ten messages may be
     *                         retrieved.
     * @var integer $timeout   optional. The number of seconds that retrieved
     *                         messages should be hidden from view in the queue.
     *                         If not specified, the default visibility timeout
     *                         of this queue is used.
     * @throws Zend_Service_Amazon_Sqs_Exception
     * @return  array an array containing one or more retrieved messages. Each
     *               message in the returned array is represented as an
     *               associative array with the following keys:
     *               - <tt>id</tt>     - the message id.
     *               - <tt>body</tt>   - the body of the message.
     *               - <tt>handle</tt> - the receipt handle of the message.
     */
    public function receive($count, $timeout = null)
    {
        if($timeout !== null && !$this->isValidVisibiltyTimeout($timeout)) {
            throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                'specified timeout falls outside the allowable range (0-7200)',
                0, $timeout);
        }

        // make sure the count is valid for amazon. valid race is 1-10.
        $count = max($count, 1);
        $count = min($count, 10);

        $params = array();

        $params['Action'] = 'ReceiveMessage';
        $params['MaxNumberOfMessages'] = $count;

        if ($timeout) {
            $params['VisibilityTimeout'] = $timeout;
        }

        try {
            $response = $this->sendRequest($params, $this->queueUrl);
        } catch (Zend_Service_Amazon_Sqs_Exception $e) {
            switch ($e->getError()) {
            case 'AWS.SimpleQueueService.NonExistentQueue':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'queue "' . $this . '" does not exist.', 0,
                    $this->queueUrl);
            default:
                throw $e;
            }
        }

        // get messages from response
        $messages = array();
        $xpath    = $response->getXPath();
        $nodes    = $xpath->query('//sqs:Message');

        foreach ($nodes as $node) {
            $id     = $xpath->evaluate('string(sqs:MessageId/text())', $node);
            $handle = $xpath->evaluate('string(sqs:ReceiptHandle/text())', $node);
            $body   = $xpath->evaluate('string(sqs:Body/text())', $node);

            $message           = array();
            $message['id']     = $id;
            $message['body']   = $body;
            $message['handle'] = $handle;

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Deletes a message from this queue
     *
     * @param string $handle the receipt handle of the message to delete.
     *
     * @return void
     *
     * @throws Zend_Service_Amazon_Sqs_Exception
     *
     */
    public function delete($handle)
    {
        $params = array();

        $params['Action'] = 'DeleteMessage';
        $params['ReceiptHandle'] = $handle;

        try {
            $this->sendRequest($params, $this->queueUrl);
        } catch (Zend_Service_Amazon_Sqs_Exception $zsase) {
            print $zsase->getErrorCode();
            throw $zsase;
        }
    }

    /**
     * Gets an associative array of one or more attributes of this queue
     *
     * Currently, Amazon SQS only allows retrieving the values of the
     * following attributes:
     *
     * - <tt>ApproximateNumberOfMessages</tt> - an approximation of the number
     *                                          of messages in this queue.
     * - <tt>VisibilityTimeout</tt>           - the default time period for
     *                                          which messages are made
     *                                          invisible when retrieved from
     *                                          this queue.
     *
     * Additionally, the special attribute <tt>All</tt> may be used to retrieve
     * all available attributes.
     *
     * @param string $name optional. The name of the attribute value to get or
     *                     <tt>All</tt> to get all available attributes. If
     *                     not specified, 'All' is used.
     *
     * @return array an associative array of available attributes. The attribute
     *               name is the array key and the attribute value is the
     *               array value.
     *
     * @throws Zend_Service_Amazon_Sqs_Exception
     *
     */
    public function getAttributes($name = 'All')
    {
        $params = array();

        $params['Action'] = 'GetQueueAttributes';
        $params['AttributeName'] = $name;

        try {
            $response = $this->sendRequest($params, $this->queueUrl);
        } catch (Zend_Service_Amazon_Sqs_Exception $e) {
            switch ($e->getError()) {
            case 'InvalidAttributeName':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'attribute name "' . $name . '" is not a valid attribute ' .
                    'name.', 0, $name);

            case 'AWS.SimpleQueueService.NonExistentQueue':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'queue "' . $this . '" does not exist.', 0,
                    $this->queueUrl);

            default:
                throw $e;
            }
        }

        $attributes = array();
        $xpath      = $response->getXPath();
        $nodes      = $xpath->query('//sqs:Attribute');

        foreach ($nodes as $node) {
            $name  = $xpath->evaluate('string(sqs:Name/text())', $node);
            $value = $xpath->evaluate('string(sqs:Value/text())', $node);

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * Sets an attribute of this queue
     *
     * Currently, Amazon SQS only allows setting the <tt>VisibilityTimeout</tt>
     * attribute. This attribute sets the default time period for which
     * messages are made invisible when retrieved from this queue.
     *
     * @param string $name  the attribute name.
     * @param mixed  $value the attribute value.
     *
     * @return void
     *
     * @throws Zend_Service_Amazon_Sqs_Exception.
     */
    public function setAttribute($name, $value)
    {
        $params = array();

        $params['Action']          = 'SetQueueAttributes';
        $params['Attribute.Name']  = $name;
        $params['Attribute.Value'] = $value;

        try {
            $this->sendRequest($params, $this->queueUrl);
        } catch (Services_Amazon_SQS_ErrorException $e) {
            switch ($e->getError()) {
            case 'InvalidAttributeName':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'attribute name "' . $name . '" is not a valid attribute ' .
                    'name.', 0, $name);

            case 'AWS.SimpleQueueService.NonExistentQueue':
                throw new Zend_Service_Amazon_Sqs_Exception('The ' .
                    'queue "' . $this . '" does not exist.', 0,
                    $this->queueUrl);

            default:
                throw $e;
            }
        }
    }

}