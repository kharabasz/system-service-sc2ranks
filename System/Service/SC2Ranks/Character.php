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
 * @see Zend_Filter_Alpha
 */
require_once 'Zend/Filter/Alpha.php';

/**
 * System_Service_SC2Ranks_Character
 *
 * This class instantiates, parses, and handles errors related to character
 * data passed to System_Service_SC2Ranks
 *
 * @category   System
 * @package    System_Service
 * @subpackage SC2Ranks
 */
class System_Service_SC2Ranks_Character
{
    /**
     * Status constants
     */
    const INVALID_MARKERS = 0;
    const INVALID_REGION  = 1;
    const INVALID_URI     = 2;
    const API_ERROR       = 3;
    const NO_RESULTS      = 4;
    const INACTIVE        = 5;
    const OK              = 6;
    
    /**
     * Status message template definitions
     *
     * @access protected
     * @var array
     */
    protected $_statusMessages = array(
        self::INVALID_MARKERS => 'Player is missing character information',
        self::INVALID_REGION  => 'An invalid region was passed with this character',
	    self::INVALID_URI     => 'Player entered an invalid bnet url',
	    self::API_ERROR       => 'SC2Ranks.com API specific error',
        self::NO_RESULTS      => 'SC2Ranks.com did not find a profile for this player',
	    self::INACTIVE        => 'This player has not played any 1vs1s on ladder',
	    self::OK              => 'OK',
	);
    
    /**
     * The status of the character
     *
     * @access protected
     * @var int
     */
    protected $_status = null;
    
    /**
     * A copy of the markers used to instantiate this character
     *
     * @access protected
     * @var array
     */
    protected $_markers = null;
    
    /**
     * A default region to query on
     *
     * @access protected
     * @var string
     */
    protected $_defaultRegion = 'us';
    
    /**
     * An array of valid region codes
     *
     * @access protected
     * @var array
     */
    protected $_regionCodes = array(
        'kr',
        'us',
        'cn',
        'sea',
        'eu',
    );
    
    /**
     * The characters regions
     *
     * @access protected
     * @var string
     */
    protected $_region = null;
    
    /**
     * The characters bnet_id
     *
     * @access protected
     * @var int
     */
    protected $_bnetId = null;
    
    /**
     * The characters name
     *
     * @access protected
     * @var string
     */
    protected $_name = null;
    
    /**
     * The characters code
     *
     * @access protected
     * @var int
     */
    protected $_code = null;
    
    /**
     * The characters division
     *
     * @access protected
     * @var string
     */
    protected $_division = 'Unknown';
    
    /**
     * The characters division rank
     *
     * @access protected
     * @var int
     */
    protected $_divisionRank = 0;
    
    /**
     * The characters league
     *
     * @access protected
     * @var string
     */
    protected $_league = 'Unknown';
    
    /**
     * The characters points
     *
     * @access protected
     * @var int
     */
    protected $_points = 0;
    
    /**
     * The characters wins
     *
     * @access protected
     * @var int
     */
    protected $_wins = 0;
    
    /**
     * The characters losses
     *
     * @access protected
     * @var int
     */
    protected $_losses = 0;
    
    /**
     * The characters region rank
     *
     * @access protected
     * @var int
     */
    protected $_regionRank = 0;
    
    /**
     * An array of any error messages
     *
     * @access protected
     * @var array
     */
    protected $_messages = array();
    
    /**
     * Constructor
     *
     * @param array $markers an array of markers to instantiate the character
     * @param string $defaultRegion default region to query in
     * @return void
     */
    public function __construct($markers = null, $defaultRegion = null)
    {
        if($markers === null)
        {
            require_once 'System/Service/SC2Ranks/Character/Exception.php';
            throw new System_Service_SC2Ranks_Character_Exception(
                'Markers are required to identify a character'
            );
        }
        
        if(!is_array($markers))
        {
            require_once 'System/Service/SC2Ranks/Character/Exception.php';
            throw new System_Service_SC2Ranks_Character_Exception(
                'The character constructor expects an array of markers'
            );
        }
        
        // Set the default region if one was passed
        if($defaultRegion !== null && in_array($defaultRegion, $this->_regionCodes))
        {
            $this->_defaultRegion = $defaultRegion;
        }
        
        // Store a copy of the markers passed
        $this->_markers = $markers;
        
        // First, parse any passed URI
        if(isset($markers['uri']) && $this->_parseUri($markers['uri']) === false)
        {
            $this->setStatus(self::INVALID_URI);
        }
        
        // Check for a name and update only if it has not been set
        if(isset($markers['name']))
        {
            if($this->getName() === null)
            {
                $this->setName($markers['name']);
            }
        }
        
        // Check for a code and update only if it has not been set
        if(isset($markers['code']))
        {
            if($this->getCode() === null)
            {
                $this->setCode($markers['code']);
            }
        }
        
        // Set the region, if one was passed
        if(isset($markers['region']))
        {
            $this->setRegion($markers['region']);
        }
        
        /**
         * A query to the API requires either a region, name, and bnet_id or
         * a region, name, and code - throw an exception otherwise
         */
        if(($this->getName() === null && $this->getBnetId() === null)
           && ($this->getName() === null && $this->getCode() === null))
        {
            require_once 'System/Service/SC2Ranks/Character/Exception.php';
            throw new System_Service_SC2Ranks_Character_Exception(
                'Could not succesfully parse markers'
            );
        }
    }
    
    /**
     * Pretty print the originally passed markers
     *
     * @return string
     */
    public function getMarkers()
    {
        $strings = array();
        foreach($this->_markers as $marker => $value)
        {
            $strings[] = $marker . ':' . $value;
        }
        
        return implode(',', $strings);
    }
    
    /**
     * Get the region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->_region === null
               ? $this->_defaultRegion
               : $this->_region;
    }
    
    /**
     * Set the characters region
     *
     * @param string $region
     * @return System_Service_SC2Ranks_Character
     */
    public function setRegion($region)
    {
        if(in_array($region, $this->_regionCodes))
        {
            $this->_region = $region;
        }
        else
        {
            $this->_region = null;
            $this->setStatus(self::INVALID_REGION);
        }
        
        return $this;
    }
    
    /**
     * Get the characters bnet_id
     *
     * @return int
     */
    public function getBnetId()
    {
        return $this->_bnetId;
    }
    
    /**
     * Set the characters bnet_id
     *
     * @param int $bnetId
     * @return System_Service_SC2Ranks_Character
     */
    public function setBnetId($bnetId)
    {
        $this->_bnetId = (int) $bnetId;
        
        return $this;
    }
    
    /**
     * Get the characters name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Set the characters name
     *
     * @param string $name
     * @return System_Service_SC2Ranks_Character
     */
    public function setName($name)
    {
        $this->_name = $name;
        
        return $this;
    }
    
    /**
     * Get the characters code
     *
     * @return int
     */
    public function getCode()
    {
        return $this->_code;
    }
    
    /**
     * Set the characters code
     *
     * @param int $code
     * @return System_Service_SC2Ranks_Character
     */
    public function setCode($code)
    {
        $this->_code = (int) $code;
        
        return $this;
    }
    
    /**
     * Get the characters division
     *
     * @return division
     */
    public function getDivision()
    {
        return $this->_division;
    }
    
    /**
     * Set the characters division
     *
     * @param string $message
     * @return System_Service_SC2Ranks_Character
     */
    public function setDivision($division)
    {
        $alpha = new Zend_Filter_Alpha(true);
        $this->_division = $alpha->filter($division);
        
        return $this;
    }
    
    /**
     * Get the characters division rank
     *
     * @return int
     */
    public function getDivisionRank()
    {
        return $this->_divisionRank;
    }
    
    /**
     * Set the characters division rank
     *
     * @param int $divisionRank
     * @return System_Service_SC2Ranks_Character
     */
    public function setDivisionRank($divisionRank)
    {
        $this->_divisionRank = (int) $divisionRank;
        
        return $this;
    }
    
    /**
     * Get the characters league
     *
     * @return string
     */
    public function getLeague()
    {
        return $this->_league;
    }
    
    /**
     * Set the characters league
     *
     * @param string $message
     * @return System_Service_SC2Ranks_Character
     */
    public function setLeague($league)
    {
        $alpha = new Zend_Filter_Alpha(true);
        $this->_league = $alpha->filter($league);
        
        return $this;
    }
    
    /**
     * Get the characters points
     *
     * @return int
     */
    public function getPoints()
    {
        return $this->_points;
    }
    
    /**
     * Set the characters points
     *
     * @param int $points
     * @return System_Service_SC2Ranks_Character
     */
    public function setPoints($points)
    {
        $this->_points = (int) $points;
        
        return $this;
    }
    
    /**
     * Get the characters wins
     *
     * @return int
     */
    public function getWins()
    {
        return $this->_wins;
    }
    
    /**
     * Set the characters wins
     *
     * @param int $wins
     * @return System_Service_SC2Ranks_Character
     */
    public function setWins($wins)
    {
        $this->_wins = (int) $wins;
        
        return $this;
    }
    
    /**
     * Get the characters losses
     *
     * @return int
     */
    public function getLosses()
    {
        return $this->_losses;
    }
    
    /**
     * Set the characters losses
     *
     * @param int $losses
     * @return System_Service_SC2Ranks_Character
     */
    public function setLosses($losses)
    {
        $this->_losses = (int) $losses;
        
        return $this;
    }
    
    /**
     * Get the characters region rank
     *
     * @return int
     */
    public function getRegionRank()
    {
        return $this->_regionRank;
    }
    
    /**
     * Set the characters region rank
     *
     * @param int $regionRank
     * @return System_Service_SC2Ranks_Character
     */
    public function setRegionRank($regionRank)
    {
        $this->_regionRank = (int) $regionRank;
        
        return $this;
    }
    
    /**
     * Check if the character has team information
     *
     * @return bool
     */
    public function isValid()
    {
        return ($this->_status === self::OK);
    }
    
    /**
     * Get any error messages
     *
     * @return string
     */
    public function getMessages()
    {
        return $this->_messages;
    }
    
    /**
     * Set an error message
     *
     * @param string $message
     * @return System_Service_SC2Ranks_Character
     */
    public function setMessage($message)
    {
        $this->_messages[] = $message;
        
        return $this;
    }
    
    /**
     * Get the characters status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }
    
    /**
     * Set the characters status
     *
     * @param string $status
     * @return System_Service_SC2Ranks_Character
     */
    public function setStatus($status)
    {
        // Throw an exception if an invalid status was provided
        if(!in_array($status, array_keys($this->_statusMessages)))
        {
            require_once 'System/Service/SC2Ranks/Character/Exception.php';
            throw new System_Service_SC2Ranks_Character_Exception(
                'Attempted to set an invalid status'
            );
        }
        
        $this->_status = $status;
        $this->setMessage($this->_statusMessages[$status]);
        
        return $this;
    }
    
    /**
     * Parse battle.net and sc2ranks.com profile URIs for character markers
     *
     * @param string $uri
     * @return bool
     */
    protected function _parseUri($uri)
    {
        $success = false;
        
        $regionCodes = implode('|', $this->_regionCodes);
        
        /**
         * Valid battle.net URIs take the following format:
         *   http://REGION.battle.net/sc2/en/profile/BNETID/1/NAME(/)
         */
        if(preg_match('#^http://(' . $regionCodes . ')\.battle\.net/sc2/en/profile/([0-9]+)/[0-9]+/([\w]+)(?:/)?$#', $uri, $matches))
        {
            $this->setRegion($matches[1]);
            $this->setBnetId($matches[2]);
            $this->setName($matches[3]);
            
            $success = true;
        }
        /**
         * Valid sc2ranks.com URIs take the following format:
         *   http://(www.)sc2ranks.com/REGION/BNETID/NAME(/)
         */
        elseif(preg_match('#^http://(?:www\.)?sc2ranks\.com/(' . $regionCodes . ')/([0-9]+)/([\w]+)(?:/)?$#', $uri, $matches))
        {
            $this->setRegion($matches[1]);
            $this->setBnetId($matches[2]);
            $this->setName($matches[3]);
            
            $success = true;
        }
        
        return $success;
    }
    
    /**
     * Create an array of POST parameters to pass to an HTTP client
     *
     * @param int $index
     * @return array
     */
    public function getRawPostParameters($index)
    {
        $parameters = array();
        
        // We prefer region, bnet_id, name
        if($this->getBnetId() !== null && $this->getName() !== null)
	    {
            $parameters['characters[' . $index . '][region]'] = urlencode($this->getRegion());
            $parameters['characters[' . $index . '][name]'] = urlencode($this->getName());
            $parameters['characters[' . $index . '][bnet_id]'] = urlencode($this->getBnetId());
        }
        // Over region, name, code
        elseif($this->getName() !== null && $this->getCode() !== null)
        {
            $parameters['characters[' . $index . '][region]'] = urlencode($this->getRegion());
            $parameters['characters[' . $index . '][name]'] = urlencode($this->getName());
            $parameters['characters[' . $index . '][code]'] = urlencode($this->getCode());
        }
        
        return $parameters;
    }
    
    /**
     * Set the characters team information
     *
     * @param array $data an array of the characters teams information
     * @param int $bracketType the target bracket to take team information from
     * @return bool
     */
    public function setTeamInformation($data, $bracketType)
    {
        $isUpdated = false;
        
        // Update the characters bnet_id if we originally did not have it
        if(isset($data['bnet_id']) && $this->getBnetId() === null)
        {
            $this->setBnetId($data['bnet_id']);
        }
        
        // Iterate over all teams to find a specific bracket
        if(isset($data['teams']) && is_array($data['teams']))
        {
            foreach($data['teams'] as $team)
            {
                if(isset($team['bracket']) && $team['bracket'] === $bracketType)
                {
                    foreach($team as $key => $value)
                    {
                        // update only those properties that we care about (they will have a getter and setter)
                        $method = 'set' . implode('', array_map('ucfirst', explode('_', $key)));
                        
                        if(method_exists($this, $method))
                        {
                            $this->$method($value);
                        }
                    }
                    
                    $isUpdated = true;
                }
            }
        }
        
        return $isUpdated;
    }
}
