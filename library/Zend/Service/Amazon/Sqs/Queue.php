<?php

require_once 'Zend/Service/Amazon/Sqs.php';

class Zend_Service_Amazon_Sqs_Queue extends Zend_Service_Amazon_Sqs
{
    protected $_queueuUrl = null;


    /**
     * Override the toString method to turn the queue url.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_queueuUrl;
    }
}