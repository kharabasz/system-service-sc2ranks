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
 * @see Zend_Config
 */
require_once 'Zend/Config.php';

/**
 * @see Zend_Http_Client
 */
require_once 'Zend/Http/Client.php';

/**
 * @see System_Service_SC2Ranks_Character
 */
require_once 'System/Service/SC2Ranks/Character.php';

/**
 * @see System_Service_SC2Ranks_Response
 */
require_once 'System/Service/SC2Ranks/Response.php';

/**
 * System_Service_SC2Ranks
 *
 * A wrapper for the SC2Ranks.com API
 *
 * @category   System
 * @package    System_Service
 * @subpackage SC2Ranks
 */
class System_Service_SC2Ranks
{
    /**
     * Base URI to the SC2Ranks.com API
     *
     * @var string
     */
    const API_SERVER = 'http://sc2ranks.com/api';
    
    /**
     * Application key required to query the API
     *
     * @access protected
     * @var string
     */
    protected $_appKey = null;
    
    /**
     * The default region for queries
     *
     * @access protected
     * @var string
     */
    protected $_defaultRegion = 'us';
    
    /**
     * Local HTTP Client used for queries
     *
     * @access protected
     * @var Zend_Http_Client
     */
    protected $_httpClient = null;
    
    /**
     * The maximum number of characters to request per query
     *
     * @access protected
     * @var int
     */
    protected $_charactersPerQuery = 50;
    
    /**
     * Constructor
     *
     * @param array|Zend_Config $options an array or object of options
     * @param null|Zend_Http_Client $httpClient an optional instance of Zend_Http_Client
     * @return void
     */
    public function __construct($options = null, Zend_Http_Client $httpClient = null)
    {
        // Convert instances of Zend_Config to an array
        if($options instanceof Zend_Config)
        {
            $options = $options->toArray();
        }
        
        if(!is_array($options))
        {
            $options = array();
        }
        
        // Set the application key, if it was passed
        if(isset($options['appKey']))
        {
            $this->setAppKey($options['appKey']);
        }
        
        if($httpClient === null)
        {
            $this->setHttpClient(new Zend_Http_Client());
        }
        else
        {
            $this->setHttpClient($httpClient);
        }
        
        // Set configuration options for the HTTP Client
        if(isset($options['httpClient']))
        {
            $this->_httpClient->setConfig($options['httpClient']);
        }
        
        if(isset($options['charactersPerQuery']))
        {
            $this->setCharactersPerQuery($options['charactersPerQuery']);
        }
    }
    
    /**
     * Get the application key
     *
     * @return string
     */
    public function getAppKey()
    {
        return $this->_appKey;
    }
    
    /**
     * Set the application key
     *
     * @param string $appKey
     * @return System_Service_SC2Ranks
     */
    public function setAppKey($appKey)
    {
        $this->_appKey = $appKey;
        
        return $this;
    }
    
    /**
     * Get the HTTP Client
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient()
    {
        return $this->_httpClient;
    }
    
    /**
     * Set the HTTP Client
     *
     * @param Zend_Http_Client $client
     * @return System_Service_SC2Ranks
     */
    public function setHttpClient(Zend_Http_Client $client)
    {
        $this->_httpClient = $client;
        
        // Limit the characters sets acceptable for the response
        $this->_httpClient->setHeaders('Accept-Charset', 'ISO-8859-1,utf-8');
        
        return $this;
    }
    
    /**
     * Get the maximum number of characters requested per query
     *
     * @return int
     */
    public function getCharactersPerQuery()
    {
        return (int) $this->_charactersPerQuery;
    }
    
    /**
     * Set the maximum number of characters requested per query - This number is
     * limited between (0,100]
     *
     * @param int $charactersPerQuery
     * @return System_Service_SC2Ranks
     */
    public function setCharactersPerQuery($charactersPerQuery)
    {
        $charactersPerQuery = (int) $charactersPerQuery;
        
        if($charactersPerQuery > 0 && $charactersPerQuery <= 100)
        {
            $this->_charactersPerQuery = $charactersPerQuery;
        }
        /**
         * Throw an exception if the number passed is out of bounds - The resulting
         * query to the API will fail to return any results
         */
        else
        {
            require_once 'System/Service/SC2Ranks/Exception.php';
            throw new System_Service_SC2Ranks_Exception(
                'charactersPerQuery must be an integer value from 1 to 100.'
            );
        }
        
        return $this;
    }
    
    
    /**
     * Perform a query for mass base character and team information
     *
     * @param array $characters an array of an array containing character information
     * @param string $defaultRegion the default region to query
     * @param int $bracketType an int indicating the bracket requested - 1 stands for 1vs1, 2 for 2vs2, etc
     * @param int $isRandom an int indication if you are querying for pre-arranged teams in the bracket
     * @return array
     */
    public function getMassBaseTeams(array $characters, $defaultRegion = 'us', $bracketType = 1, $isRandom = 0)
    {
        // Throw an exception since the application key is required
        if($this->getAppKey() === null)
        {
            require_once 'System/Service/SC2Ranks/Exception.php';
            throw new System_Service_SC2Ranks_Exception(
                'An application key is required to make API requests'
            );
        }
        
        // the full uri of the query
        $uri = self::API_SERVER . '/mass/base/teams/?appKey=' . $this->getAppKey();
        
        /**
         * bracketType is limited to the types of brackets supported on battle.net.
         * These include 1vs1, 2vs2, 3vs3, and 4vs4 - isRandom is either true or false
         * depending if you are querying for pre-arranged teams in the bracket. Note that for
         * the 1vs1 bracket, teams cannot be pre-arranged.
         */
        if(!in_array($bracketType, array(1, 2, 3, 4))
           || !in_array($isRandom, array(0, 1)))
        {
            require_once 'System/Service/SC2Ranks/Exception.php';
            throw new System_Service_SC2Ranks_Exception(
                'bracketType must be an integer value from 1 to 4 & isRandom must be an integer value from 0 to 1'
            );
        }
        
        // Parse the character data passed to the function
        foreach($characters as $index => $markers)
        {
            try
            {
                $characters[$index] = new System_Service_SC2Ranks_Character($markers, $defaultRegion);
            }
            // Remove any characters that failed to parse
            catch(System_Service_SC2Ranks_Character_Exception $exception)
            {
                unset($characters[$index]);
                continue;
            }
        }
        
        /**
         * Splits the characters passed into chunks limited by the defined maximum amount of
         * characters sent per query. This step is very important as the query will error if you
         * pass too few or too many characters. Furthermore, it is suggested that you do not go above
         * the number this service sets by default (50) as the API is known to timeout/experience
         * performance hiccups when queried with large chunks.
         */
        $chunks = array_chunk($characters, $this->getCharactersPerQuery());
        
        foreach($chunks as $chunk)
        {
            /**
             * Prepare all parameters as POST parameters, This is required
             */
            $parameters = array(
                'team[bracket]' => (int) $bracketType,
                'team[is_random]' => (int) $isRandom,
            );
            
            foreach($chunk as $index => $character)
            {
                $rawPostParameters = $character->getRawPostParameters($index);
                if(!empty($rawPostParameters))
                {
                    $parameters = array_merge($parameters, $rawPostParameters);
                }
            }
            
            // Perform a query
            $response = $this->_post($uri, $parameters);
            
            foreach($chunk as $index => $character)
            {
                if($response->isValid())
                {
                    if($response->hasCharacter($character->getName()))
                    {
                        if($character->setTeamInformation($response->getCharacter($character->getName()), $bracketType))
                        {
                            $character->setStatus(System_Service_SC2Ranks_Character::OK);
                        }
                        else
                        {
                            $character->setStatus(System_Service_SC2Ranks_Character::INACTIVE);
                        }
                    }
                    else
                    {
                        $character->setStatus(System_Service_SC2Ranks_Character::NO_RESULTS);
                    }
                }
                else
                {
                    $character->setStatus(System_Service_SC2Ranks_Character::API_ERROR);
                    $character->setMessage($response->getErrorMessage());
                }
            }
        }
        
        // Unchunk and return an array of System_Service_SC2Ranks_Character
        return call_user_func_array('array_merge', $chunks);
    }
    
    /**
     * Post a query to the SC2Ranks.com API
     *
     * @param string $uri the target uri
     * @param array $parameters an array of post parameters
     * @return System_Service_SC2Ranks_Response
     */
    protected function _post($uri, $parameters)
    {
        $httpClient = $this->getHttpClient();
        
        $httpClient->setUri($uri)
                   ->setParameterPost($parameters);
        
        try
        {
            $response = $httpClient->request(Zend_Http_Client::POST);
        }
        catch(Zend_Exception $exception)
        {
            $response = $exception->getMessage();
        }
        
        /**
         * Make sure all the request-specific parameters are cleared - This ensures that GET and
         * POST parameters, request body and request-specific headers are reset and are not
         * reused during the next request.
         */
        $httpClient->resetParameters();
        
        // Let the System_Service_SC2Ranks_Response conveniance class handle the response
        return new System_Service_SC2Ranks_Response($response);
    }
}
