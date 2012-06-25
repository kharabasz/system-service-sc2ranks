<?php

require_once 'Zend/Json.php';

require_once 'System/Service/SC2Ranks/Character.php';

class CharacterTest extends PHPUnit_Framework_TestCase
{
    public function testSettingTeamInformation()
    {
        $data = Zend_Json::decode(
            '{"achievement_points":1900,"region":"us","updated_at":"2011-11-03T02:34:17Z","portrait"'.
            ':{"column":0,"row":5,"icon_id":3},"teams":[{"division":"Division Khaydarin Sierra","ratio"'.
            ':"1.00","division_rank":42,"updated_at":"2011-11-06T22:04:04Z","fav_race":"terran","points"'.
            ':0,"world_rank":1844,"wins":4,"bracket":4,"league":"diamond","is_random":false,"losses"'.
            ':0,"region_rank":596},{"division":"Division Roach Foxtrot","ratio":"1.00","division_rank"'.
            ':80,"updated_at":"2011-11-09T17:06:01Z","fav_race":"terran","points":0,"world_rank":2462,"wins"'.
            ':1,"bracket":3,"league":"master","is_random":false,"losses":0,"region_rank":914},{"'.
            'division":"Division Nahaan Indigo","ratio":"1.00","division_rank":94,"updated_at":"'.
            '2011-11-09T05:41:20Z","fav_race":"terran","points":0,"world_rank":3830,"wins":1,"bracket":2,"'.
            'league":"master","is_random":true,"losses":0,"region_rank":1593},{"division":"Grandmaster","'.
            'ratio":"0.63","division_rank":115,"updated_at":"2011-11-09T23:00:06Z","fav_race":"zerg","points"'.
            ':328,"world_rank":416,"wins":26,"bracket":1,"league":"grandmaster","is_random":false,"losses":15,"'.
            'region_rank":106}],"character_code":862,"name":"VTgiX","bnet_id":295980,"id":40775}'
        );
        
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'vtgiX', 'code' => 862, 'region' => 'us'
        ));
        
        $result = $character->setTeamInformation($data, 1);
        
        $this->assertEquals(true, $result);
        $this->assertEquals('Grandmaster', $character->getDivision());
        $this->assertEquals(115, $character->getDivisionRank());
        $this->assertEquals(328, $character->getPoints());
        $this->assertEquals(26, $character->getWins());
        $this->assertEquals(15, $character->getLosses());
        $this->assertEquals('grandmaster', $character->getLeague());
        $this->assertEquals(106, $character->getRegionRank());
        $this->assertEquals(295980, $character->getBnetId());
        
        $result = $character->setTeamInformation($data, 2);
        
        $this->assertEquals(true, $result);
        $this->assertEquals('Division Nahaan Indigo', $character->getDivision());
        $this->assertEquals(94, $character->getDivisionRank());
        $this->assertEquals(0, $character->getPoints());
        $this->assertEquals(1, $character->getWins());
        $this->assertEquals(0, $character->getLosses());
        $this->assertEquals('master', $character->getLeague());
        $this->assertEquals(1593, $character->getRegionRank());
        $this->assertEquals(295980, $character->getBnetId());
        
        $data = Zend_Json::decode(
            '{"achievement_points":1900,"region":"us","updated_at":"2011-11-03T02:34:17Z","portrait"'.
            ':{"column":0,"row":5,"icon_id":3},"character_code":862,"name":"VTgiX","bnet_id"'.
            ':295980,"id":40775}'
        );
        
        $result = $character->setTeamInformation($data, 2);
        $this->assertEquals(false, $result);
        
    }
    
    public function testRawPostParameters()
    {
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'heavonearth', 'code' => '2290485'
        ));
        
        $expected = array(
            'characters[88][code]' => 2290485,
            'characters[88][name]' => 'heavonearth',
            'characters[88][region]' => 'us',
        );
        $this->assertEquals($expected, $character->getRawPostParameters(88));
        
        $character = new System_Service_SC2Ranks_Character(array(
            'uri' => 'http://us.battle.net/sc2/en/profile/902213/1/VPSuppy/'
        ));
        $expected = array(
            'characters[88][bnet_id]' => 902213,
            'characters[88][name]' => 'VPSuppy',
            'characters[88][region]' => 'us',
        );
        $this->assertEquals($expected, $character->getRawPostParameters(88));
    }
    
    public function testInvalidRegionAssignment()
    {
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'heavonearth', 'code' => '2290485'
        ), 'krr');
        $this->assertEquals('us', $character->getRegion());
        
        $character->setRegion('can');
        $this->assertEquals('us', $character->getRegion());
        $this->assertEquals(System_Service_SC2Ranks_Character::INVALID_REGION, $character->getStatus());
    }
    
    public function testSettersAndGetters()
    {
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'heavonearth', 'code' => '2290485'
        ));
        
        $character->setBnetId(13);
        $this->assertEquals(13, $character->getBnetId());
        
        $character->setName('TEST');
        $this->assertEquals('TEST', $character->getName());
        
        $character->setCode(24);
        $this->assertEquals(24, $character->getCode());
        
        $character->setDivision('alpha and omega');
        $this->assertEquals('alpha and omega', $character->getDivision());
        
        $character->setDivisionRank(1);
        $this->assertEquals(1, $character->getDivisionRank());
        
        $character->setLeague('masters');
        $this->assertEquals('masters', $character->getLeague());
        
        $character->setPoints(1250);
        $this->assertEquals(1250, $character->getPoints());
        
        $character->setWins(99);
        $this->assertEquals(99, $character->getWins());
        
        $character->setLosses(29);
        $this->assertEquals(29, $character->getLosses());
        
        $character->setRegionRank(1300);
        $this->assertEquals(1300, $character->getRegionRank());
        
        $character->setStatus(System_Service_SC2Ranks_Character::API_ERROR);
        $this->assertEquals(false, $character->isValid());
        
        $character->setStatus(System_Service_SC2Ranks_Character::OK);
        $this->assertEquals(true, $character->isValid());
        
        $expected = array(
            0 => 'SC2Ranks.com API specific error',
            1 => 'OK',
        );
        $this->assertEquals($expected, $character->getMessages());
        
        $wasCaught = false;
        try
        {
            $character->setStatus(134);
        }
        catch(System_Service_SC2Ranks_Character_Exception $e)
        {
            $this->assertEquals('Attempted to set an invalid status', $e->getMessage());
            $wasCaught = true;
        }
        $this->assertEquals(true, $wasCaught);
    }
    
    public function testDefaultRegion()
    {
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'heavonearth', 'code' => '2290485'
        ));
        $this->assertEquals('us', $character->getRegion());
        
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'heavonearth', 'code' => '2290485'
        ), 'kr');
        $this->assertEquals('kr', $character->getRegion());
    }
    
    public function testConstructor()
    {
        /**
         * Test properly formated battle.net url
         */
        $character = new System_Service_SC2Ranks_Character(array(
            'uri' => 'http://us.battle.net/sc2/en/profile/902213/1/VPSuppy/'
        ));
        $this->assertEquals('VPSuppy', $character->getName());
        $this->assertEquals('us', $character->getRegion());
        $this->assertEquals(902213, $character->getBnetId());
        
        /**
         * Test properly formated sc2ranks.com url
         */
        $character = new System_Service_SC2Ranks_Character(array(
            'uri' => 'http://sc2ranks.com/us/2290485/heavonearth'
        ));
        $this->assertEquals('heavonearth', $character->getName());
        $this->assertEquals('us', $character->getRegion());
        $this->assertEquals(2290485, $character->getBnetId());
        
        /**
         * Test properly formated array of markers
         */
        $character = new System_Service_SC2Ranks_Character(array(
            'name' => 'heavonearth', 'code' => '2290485', 'region' => 'kr'
        ));
        $this->assertEquals('heavonearth', $character->getName());
        $this->assertEquals('kr', $character->getRegion());
        $this->assertEquals(2290485, $character->getCode());
        
        /**
         * Test improper sc2ranks.com url
         */
        $wasCaught = false;
        try
        {
            $character = new System_Service_SC2Ranks_Character(array(
                'uri' => 'http://sc2ranks.com/krr/2016129/PuMa'
            ));
        }
        catch(System_Service_SC2Ranks_Character_Exception $e)
        {
            $wasCaught = true;
        }
        $this->assertEquals(true, $wasCaught);
        
        /**
         * Test improper url with correct markers
         */
        $wasCaught = false;
        try
        {
            $character = new System_Service_SC2Ranks_Character(array(
                'uri' => 'http://google.com', 'name' => 'RevDoctor', 'code' => 178,
            ));
        }
        catch(System_Service_SC2Ranks_Character_Exception $e)
        {
            $wasCaught = true;
        }
        $this->assertEquals(false, $wasCaught);
        $this->assertEquals('RevDoctor', $character->getName());
        $this->assertEquals(178, $character->getCode());
        $this->assertEquals(System_Service_SC2Ranks_Character::INVALID_URI, $character->getStatus());
    }
    
    /**
     * @expectedException System_Service_SC2Ranks_Character_Exception
     * @expectedExceptionMessage Could not succesfully parse markers
     */
    public function testEmptyMarkersToConstructor()
    {
        $character = new System_Service_SC2Ranks_Character(array());
    }
    
    /**
     * @expectedException System_Service_SC2Ranks_Character_Exception
     * @expectedExceptionMessage The character constructor expects an array of markers
     */
    public function testInvalidTypeToConstructor()
    {
        $character = new System_Service_SC2Ranks_Character(1);
    }
    
    /**
     * @expectedException System_Service_SC2Ranks_Character_Exception
     * @expectedExceptionMessage Markers are required to identify a character
     */
    public function testNullMarkersToConstructor()
    {
        $character = new System_Service_SC2Ranks_Character();
    }
}
