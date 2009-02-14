<?php

require_once 'Zend/Http/Response.php';

class Zend_Service_Amazon_Sqs_Response {
    /**
     * XML namespace used for SQS responses.
     */
    const XML_NAMESPACE = 'http://queue.amazonaws.com/doc/2008-01-01/';

    /**
     * The original HTTP response
     *
     * This contains the response body and headers.
     *
     * @var Zend_Http_Response
     */
    private $_httpResponse = null;

    /**
     * The response document object
     *
     * @var DOMDocument
     */
    private $_document = null;

    /**
     * The response XPath
     *
     * @var DOMXPath
     */
    private $_xpath = null;

    /**
     * Last error code
     *
     * @var integer
     */
    private $_errorCode = 0;

    /**
     * Last error message
     *
     * @var string
     */
    private $_errorMessage = '';

    /**
     * Creates a new high-level SQS response object
     *
     * @param Zend_Http_Response $httpResponse the HTTP response.
     */
    public function __construct(Zend_Http_Response $httpResponse)
    {
        $this->_httpResponse = $httpResponse;
    }

    /**
     * Gets the XPath object for this response
     *
     * @return DOMXPath the XPath object for response.
     */
    public function getXPath()
    {
        if ($this->_xpath === null) {
            $document = $this->getDocument();
            if ($document === false) {
                $this->_xpath = false;
            } else {
                $this->_xpath = new DOMXPath($document);
                $this->_xpath->registerNamespace('sqs',
                    self::XML_NAMESPACE);
            }
        }

        return $this->_xpath;
    }

    /**
     * Gets the document object for this response
     *
     * @return DOMDocument the DOM Document for this response.
     */
    public function getDocument()
    {
        try {
            $body = $this->_httpResponse->getBody();
        } catch (Zend_Http_Exception $e) {
            $body = false;
        }

        if ($this->_document === null) {
            if ($body !== false) {
                // turn off libxml error handling
                $errors = libxml_use_internal_errors();

                $this->_document = new DOMDocument();
                if (!$this->_document->loadXML($body)) {
                    $this->_document = false;
                }

                // reset libxml error handling
                libxml_clear_errors();
                libxml_use_internal_errors($errors);
            } else {
                $this->_document = false;
            }
        }

        return $this->_document;
    }
}