<?php
/**
 * System Services
 *
 * @category   System
 * @package    System_Service
 * @subpackage SC2Ranks
 * @author     Kacper Harabasz <kacper.harabasz@gmail.com>
 * @version    0.75.430 beta
 * @since      Zend Framework 1.11.0
 */

/**
 * @see Zend_Json
 */
require_once 'Zend/Json.php';

/**
 * @see Zend_Http_Response
 */
require_once 'Zend/Http/Response.php';

/**
 * @see System_Service_SC2Ranks_Character
 */
require_once 'System/Service/SC2Ranks/Character.php';

/**
 * System_Service_SC2Ranks_Response
 *
 * This class handles decorating and parsing Zend_Http_Response objects from
 * the SC2Ranks.com API - It provides a convenience feature by handling errors
 * 
 * @category   System
 * @package    System_Service
 * @subpackage SC2Ranks
 */
class System_Service_SC2Ranks_Response
{
    /**
     * The status of the response (if it suceeded or not)
     *
     * @access protected
     * @var bool
     */
    protected $_status = null;
    
    /**
     * An error message
     *
     * @access protected
     * @var string
     */
    protected $_errorMessage = null;
    
    /**
     * Hash of character information returned on a succesful query
     *
     * @access protected
     * @var array
     */
    protected $_characters = array();
    
    /**
     * Constructor
     *
     * @param string|Zend_Http_Response $response a string containing a message or an HTTP response object
     * @return void
     */
    public function __construct($response)
    {
        /**
         * If the response is a string this indicates that the HTTP client failed before sending
         * a query and that we most likely have an the message from the resulting exception
         */
        if(is_string($response))
        {
            $this->setStatus(false);
            $this->setErrorMessage($response);
        }
        // An instance of an HTTP Response indicated we succesfully sent a query to the API
        elseif($response instanceof Zend_Http_Response)
        {
            // But does not necessarily mean we queried the correct uri or that the API was reachable 
            if($response->getStatus() !== 200)
            {
                $this->setStatus(false);
                $this->setErrorMessage('Query returned status ' . $response->getStatus());
            }
            else
            {
                $body = Zend_Json::decode($response->getBody());
                
                // Check if the query returned an error message
                if(isset($body['error']))
                {
                    $this->setStatus(false);
                    $this->setErrorMessage($body['error']);
                }
                // Otherwise parse the json object in the body of the response
                else
                {
                    $this->setStatus(true);
                    $this->_parseResponseBody($body);
                }
            }
        }
        // Throw an exception if we are tryiing to wrap anything non standard
        else
        {
            require_once 'System/Service/SC2Ranks/Response/Exception.php';
            throw new System_Service_SC2Ranks_Response_Exception(
                'The response passed could not be wrapped'
            );
        }
    }
    
    /**
     * Get the status of the response
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->_status;
    }
    
    /**
     * Set the status of the response
     *
     * @param bool $status
     * @return System_Service_SC2Ranks_Response
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        
        return $this;
    }
    
    /**
     * Get the error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }
    
    /**
     * Set an error message
     *
     * @param string $errorMessage
     * @return System_Service_SC2Ranks_Response
     */
    public function setErrorMessage($errorMessage)
    {
        $this->_errorMessage = $errorMessage;
        
        return $this;
    }
    
    /**
     * Check if the response held information on a character
     *
     * @param string $name the name of the character
     * @return bool
     */
    public function hasCharacter($name)
    {
        return isset($this->_characters[strtolower($name)]);
    }
    
    /**
     * Return information on a character
     *
     * @param string $name the name of the character
     * @return array
     */
    public function &getCharacter($name)
    {
        return $this->_characters[strtolower($name)];
    }
    
    /**
     * Key the characters returned in the response by name
     *
     * @param array $body
     * @return System_Service_SC2Ranks_Response
     */
    protected function _parseResponseBody($body)
    {
        foreach($body as $character)
        {
            if(isset($character['name']))
            {
                $this->_characters[strtolower($character['name'])] = $character;
            }
        }
        
        return $this;
    }
}