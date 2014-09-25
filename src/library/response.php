<?php

/**
 * Representation of REST service response message
 *
 * @package \Tranquility\Response
 * @author Andrew Patterson <patto@live.com.au>
 */

namespace Tranquility;

use \Tranquility\Enum\System\MessageLevel   as EnumMessageLevel;
use \Tranquility\Enum\System\HttpStatusCode as EnumHttpStatusCodes;

class Response {
    
    /**
     * Main content to be returned in the response
     * @var array
     */
    protected $_content = array();
    
    /**
     * Any messages to be returned in the response. Each message should be in an
     * individual array containing keys for code, level, error text and 
     * (optionally) the associated fieldID.
     * @var array
     */
    protected $_messages = array();
    
    /**
     * Message metadata - usually will be determined from the content of the
     * response message
     * @var array
     */
    protected $_meta = array();
    
    /**
     * Unique transaction ID generated on any create / update / delete action
     */
    protected $_transactionId = 0;
    
    /**
     * HTTP response code 
     * @var int 
     */
    protected $_httpResponseCode = 0;
    
    /**
     * Constructor
     * 
     * @param array $options Valid keys are:
     *                         - content
     *                         - messages
     *                         - meta
     */
    public function __construct($options = array()) {
        // Set message content if supplied
        $content = Utility::extractValue($options, 'content', array());
        $this->setContent($content, false);
        
        // If any messages have been supplied, add them now
        if (isset($options['messages']) && is_array($options['messages'])) {
            $this->addMessages($options['messages']);
        }
        
        // Calculate metadata
        $this->calculateMetadata();
        
        // Add / update metadata if supplied
        $meta = Utility::extractValue($options, 'meta', array());
        $this->setMetadata($meta); 
        
        // Set HTTP response code
        if (isset($options['responseCode'])) {
            $this->setResponseCode($options['responseCode']);
        }
    }
    
    /**
     * Sets the main content that will be returned in the REST response. 
     * 
     * @param array $content The actual content of the message
     * @param string $calculateMetadata If true, metadata for the response will
     *                                  be automatically recalculated.
     * @return boolean
     * @throws \Tranquility\Exception
     */
    public function setContent($content = array(), $calculateMetadata = true) {
        // Content must be defined as an array
        if (!is_array($content)) {
            throw new Exception('REST service response content must be defined as an array');
        }
        $this->_content = $content;
        
        // If the flag has been set, recalculate metadata now
        if ($calculateMetadata) {
            $this->calculateMetadata();
        }
        
        return true;
    }
    
    /**
     * Returns the currently set content for the message
     * 
     * @return array
     */
    public function getContent() {
        return $this->_content;
    }
    
    /**
     * Clears any existing messages, and then sets the supplied array of new messages
     * 
     * @param array $messages
     * @return bool
     */
    public function setMessages($messages) {
        $this->clearMessages();
        return $this->addMessages($messages);
    }
    
    /**
     * Adds the supplied array of messages to the existing set in the response
     * 
     * @param array $messages
     * @return boolean
     * @throws \Tranquility\Exception
     */
    public function addMessages($messages) {
        // Messages must be supplied as an array
        if (!is_array($messages)) {
            throw new Exception('List of messages must be supplied as an array');
        }
        
        foreach ($messages as $message) {
            // Check mandatory message fields have been provided
            if (!isset($message['code']) || !isset($message['text']) || !isset($message['level'])) {
                throw new Exception('Message must contain a code, text and level');
            }
            
            // Add message
            $fieldId = Utility::extractValue($message, 'fieldId', null);
            $this->addMessage($message['code'], $message['text'], $message['level'], $fieldId);
        }
        
        return true;
    }
    
    /**
     * Add a single new informational / error message to the existing set
     * of messages in the response
     * 
     * @param int $code
     * @param string $text
     * @param string $level
     * @param string $fieldId Optional. Relates a message to a particular HTML form element by ID
     */
    public function addMessage($code, $text, $level, $fieldId = null) {
        // Validate message level
        if (!EnumMessageLevel::isValidValue($level)) {
            throw new Exception('Invalid message level supplied: '.$level);
        }
        
        // Add message to set
        $this->_messages[] = array(
            'code' => $code,
            'text' => $text,
            'level' => $level,
            'fieldId' => $fieldId
        );
        
        return true;
    }
    
    /**
     * Returns the set of messages associated with the response
     * @return array
     */
    public function getMessages() {
        return $this->_messages;
    }
    
    /**
     * Clear any existing informational/error messages from the response 
     * @return boolean
     */
    public function clearMessages() {
        $this->_messages = array();
        return true;
    }
    
    /**
     * Sets the metadata for the message. Note that this will overwrite any
     * existing automatically determined metadata
     * 
     * @param array $metadata
     * @return boolean
     * @throws \Tranquility\Exception
     */
    public function setMetadata($metadata = array()) {
        // Content must be defined as an array
        if (!is_array($metadata)) {
            throw new Exception('REST service response metadata must be defined as an array');
        }
        
        $merged = array_merge($this->_meta, $metadata);
        $this->_meta = $merged;
        return true;
    }
    
    /**
     * Returns the metadata associated with the response
     * @return array
     */
    public function getMetadata() {
        return $this->_meta;
    }
    
    /**
     * Automatically determines metadata based on the message content
     * @return boolean
     */
    public function calculateMetadata() {
        // Count the number of items in the response
        if (is_array($this->_content) && reset($this->_content) !== false) {
            $count = count(reset($this->_content));
        } else {
            $count = 0;
        }
        $this->_meta['count'] = $count;

        // Add HTTP response code
        $this->_meta['code'] = $this->getResponseCode();
        
        // Add transaction ID
        if ($this->getTransactionId() !== 0) {
            $this->_meta['transactionId'] = $this->getTransactionId();
        }
        
        // TODO: Additional metadata?
        return true;
    }
    
    /** 
     * Returns the number of items in the response content array
     * @return int
     */
    public function getItemCount() {
        return $this->_meta['count'];
    }
    
    /**
     * Returns the number of messages in the response metadata
     * @return int
     */
    public function getMessageCount() {
        return count($this->_messages);
    }
    
    public function addTransactionId($transactionId) {
        $this->_transactionId = $transactionId;
        $this->_meta['transactionId'] = $transactionId;
    }
    
    public function getTransactionId() {
        return $this->_transactionId;
    }
    
    
    /**
     * Returns true if the specified message code is present as a message in the
     * response object
     * 
     * @param int $code
     * @return boolean True if the message code is present in the response
     */
    public function containsMessageCode($code) {
        foreach ($this->_messages as $message) {
            if ($message['code'] == $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Set the HTTP status code for the response
     * @param int $code
     * @return boolean
     * @throws \Tranquility\Exception
     */
    public function setResponseCode($code) {
        if (!EnumHttpStatusCodes::isValidValue($code)) {
            throw new Exception('Unknown HTTP code supplied: '.$code);
        }
        
        $this->_httpResponseCode = $code;
        return true;
    }
    
    /**
     * Returns the HTTP status code
     * @return int
     */
    public function getResponseCode() {
        return $this->_httpResponseCode;
    }
    
    /**
     * If the response code has not been set to 200, will return true
     * @return boolean
     */
    public function hasErrors() {
        if ($this->getResponseCode() != EnumHttpStatusCodes::OK) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns the full response object formatted as an array
     * @return array
     */
    public function toArray() {
        $response = array(
            'meta' => $this->_meta,
            'messages' => $this->_messages,
            'response' => $this->_content
        );
        
        return $response;
    }
}