<?php

$characters = array(
    array('uri' => 'http://us.battle.net/sc2/en/profile/902213/1/VPSuppy/'),
    array('uri' => 'http://sc2ranks.com/us/2290485/heavonearth'),
    array('uri' => 'http://sc2ranks.com/us/774764/JasonX/'),
    array('uri' => 'http://us.battle.net/sc2/en/profile/1830760/1/CombatEX'),
    array('name' => 'vileSpanshwa', 'code' => '785', 'region' => 'us'),
    array('name' => 'vtgiX', 'code' => 862, 'region' => 'us'),
    array('name' => 'xSixShadow', 'code' => 635, 'region' => 'us'),
    array('uri' => 'http://sc2ranks.com/cn/16844/SiXthFinGer'),
    array('uri' => 'http://sc2ranks.com/sea/372373/xGKingMafia'),
    array('uri' => 'http://sc2ranks.com/eu/884897/GLSnute'),
    array('uri' => 'http://sc2ranks.com/kr/2217039/NEXLife'),
    array('uri' => 'http://sc2ranks.com/krr/2016129/PuMa'),
    array('uri' => 'http://google.com', 'name' => 'RevDoctor', 'code' => 178),
);

require_once 'System/Service/SC2Ranks.php';

$sc2ranks = new System_Service_SC2Ranks(array(
    'appKey' => 'cstarleague.com',
    'httpClient' => array(
        'maxredirects' => 0,
        'timeout' => 60,
        'keepalive' => true,
    ),
));

$results = $sc2ranks->getMassBaseTeams($characters);

foreach($results as $character)
{
    print $character->getName() . PHP_EOL;
    
    if($character->isValid())
    {
        print ' => ' . $character->getDivision() . ' (#' .
              $character->getDivisionRank() . ') ' .
              ucfirst($character->getLeague()) . ' ' .
              $character->getPoints() . ' pts (' .
              $character->getWins() . '-' .
              $character->getLosses() . ') ' . PHP_EOL;
    }
    else
    {
        foreach($character->getMessages() as $message)
        {
            print ' => ' . $message . PHP_EOL;
        }
    }
    
    print PHP_EOL;
}
