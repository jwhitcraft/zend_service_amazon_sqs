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

    public function testDeleteThrowsExceptionOnInvalidQueue()
    {
        $class = new Zend_Service_Amazon_Sqs_Queue('test153513513');
        try {
            //$class->delete('Z2hlcm1hbi5kZXNrdG9wLmFtYXpvbi5jb20=:AAABFoNJa/AAAAAAAAAANwAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAHA=');
        } catch (Zend_Service_Amazon_Sqs_Exception $e) {
            print $e->getMessage();
        }
    }
}

