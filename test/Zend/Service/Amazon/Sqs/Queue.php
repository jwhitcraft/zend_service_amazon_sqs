<?php

require_once 'Zend/Service/Amazon/Sqs/Queue.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Zend_Service_Amazon_Sqs_Queue test case.
 */
class Queue extends PHPUnit_Framework_TestCase
{

    /**
     * @var Zend_Service_Amazon_Sqs_Queue
     */
    private $Zend_Service_Amazon_Sqs_Queue;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->Zend_Service_Amazon_Sqs_Queue = new Zend_Service_Amazon_Sqs_Queue('', '');

    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated Queue::tearDown()


        $this->Zend_Service_Amazon_Sqs_Queue = null;

        parent::tearDown();
    }

    /**
     * Tests Zend_Service_Amazon_Sqs_Queue->listQueues()
     */
    public function testListQueues()
    {
        $return = $this->Zend_Service_Amazon_Sqs_Queue->listQueues();

        $this->assertType('array', $return);

        var_dump($return);

    }

}

