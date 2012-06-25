<?php

require_once 'Zend/Http/Client.php';

require_once 'System/Service/SC2Ranks.php';

class SC2RanksTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $client = new Zend_Http_Client();
        
        $sc2ranks = new System_Service_SC2Ranks(array(
            'appKey' => 'example.org',
            'httpClient' => array(
                'maxredirects' => 0,
                'timeout' => 60,
                'keepalive' => true,
            ),
            'charactersPerQuery' => 50,
        ), $client);
        
        $this->assertEquals('example.org', $sc2ranks->getAppKey());
        $this->assertEquals(50, $sc2ranks->getCharactersPerQuery());
        
        $this->assertTrue($client === $sc2ranks->getHttpClient());
    }
    
    /**
     * @expectedException System_Service_SC2Ranks_Exception
     * @expectedExceptionMessage An application key is required to make API requests
     */
    public function testExceptionThrownWhenMissingAppKey()
    {
        $sc2ranks = new System_Service_SC2Ranks();
        $sc2ranks->getMassBaseTeams(array(), 'us', 15, 3);
    }
    
    public function testSettersAndGetters()
    {
        $sc2ranks = new System_Service_SC2Ranks(array(
            'appKey' => 'example.org',
            'httpClient' => array(
                'maxredirects' => 0,
                'timeout' => 60,
                'keepalive' => true,
            ),
            'charactersPerQuery' => 50,
        ));
        
        $client = new Zend_Http_Client();
        $this->assertFalse($client === $sc2ranks->getHttpClient());
        
        $sc2ranks->setHttpClient($client);
        $this->assertTrue($client === $sc2ranks->getHttpClient());
        
        $sc2ranks->setAppKey('google.com');
        $this->assertEquals('google.com', $sc2ranks->getAppKey());
        
        $sc2ranks->setCharactersPerQuery(1);
        $this->assertEquals(1, $sc2ranks->getCharactersPerQuery());
        
        $wasCaught = false;
        try
        {
            $sc2ranks->setCharactersPerQuery(101);
        }
        catch(System_Service_SC2Ranks_Exception $exception)
        {
            $wasCaught = true;
        }
        
        $this->assertEquals(true, $wasCaught);
    }
    
    /**
     * @expectedException System_Service_SC2Ranks_Exception
     * @expectedExceptionMessage bracketType must be an integer value from 1 to 4 & isRandom must be an integer value from 0 to 1
     */
    public function testExceptionThrownByInvalidBracketTypeOrIsRandom()
    {
        $sc2ranks = new System_Service_SC2Ranks(array(
            'appKey' => 'example.org',
        ));
        
        $sc2ranks->getMassBaseTeams(array(), 'us', 15, 3);
    }
}
