<?php

require_once 'Zend/Service/Amazon/Sqs.php';

class Zend_Service_Amazon_Sqs_Queue extends Zend_Service_Amazon_Sqs
{
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
            //$queue = new Services_Amazon_SQS_Queue($url, $this->account, '', $this->request);

            $queues[] = $url;
        }

        return $queues;
    }

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
        } catch (Services_Amazon_SQS_ErrorException $e) {
            switch ($e->getCode()) {
            case 'AWS.SimpleQueueService.QueueDeletedRecently':
                $msg = 'The queue "' . $name . '" was deleted recently. Please wait ' .
                    '60 seconds after deleting a queue before creating a ' .
                    'queue of the same name.';

            case 'AWS.SimpleQueueService.QueueNameExists':
                $msg = 'The queue "' . $name . '" already exists. To set a ' .
                    'different visibility timeout, use the ' .
                    'Services_Amazon_SQS_Queue::setAttribute() method.';

            default:
                throw $e;
            }
        }

        $xpath    = $response->getXPath();
        $queueUrl = $xpath->evaluate('string(//sqs:QueueUrl/text())');

        $queue = new Services_Amazon_SQS_Queue($queueUrl, $this->account, '',
            $this->request);

        return $queue;
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