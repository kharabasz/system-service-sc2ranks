System_Service_SC2Ranks
=======================

A wrapper for the http://www.sc2ranks.com API

Introduction
------------

SC2Ranks.com is a website that aggregates Battle.net profiles for StarCraft II accounts across
each division and region (server). Blizzard Entertainment does not provide an easy to access list of
player profiles so the website relies on cascade spidering through discovered profile URIs, manually
entered profile URIs, and spidering through the official StarCraft II forums to find profiles.

SC2Ranks.com provides an API to ease the process of mining profile data it has gathered.
Character information can be queried by passing the region, battle.net id, and name of a character
or the region, name, and code of a character. Characters that are not in the database are
automatically queued if a battle.net id is passed. Characters queried by code will not auto queue as
you cannot infer a battle.net id from a character code.

Before October 2010, it was enough to know a player's character name and code to find their
profile. Following this date, Blizzard restructured its StarCraft II community site (including the
default URI for profiles) and removed the ability to do this. SC2Ranks still allows queries on
character codes because the website had associated character codes with many profiles. The number of
profiles that return when queried by code continues to shrink due to a variety of factors. Using
character codes to query should be avoided when possible.

Restrictions
------------

Any website that uses information gathered from SC2Ranks.com must link back to the website
depending on how the data is used. Data cannot be used for mobile applications without explicit
permission.

Application keys are required for all API requests. Not passing an application key will result
in a no_key error. Keys are used for statistics on requests. They are not given out, it is simply
required to provide the the domain the data is shown on.

All queries should be cached. Keep this in mind when you make use of this wrapper as domains and
IPs that abuse the API will be blocked.

Usage
-----

This wrapper currently exposes access to one query: Mass base character + team information
For a typical website, this query is enough to handle all data needs.

This query can pull base character and team information for up to 100 characters at once. The
query returns an array of characters in a json, since XML is deemed to verbose. Parameters must be
passed through POST. I suggest you do not query more than 50 characters at a time as the API is
known to timeout or show slow performance for queries over this number. This is particularly true if
you still query for characters using region, name, and code.

Please see example.php for a sample script.
    
Typically, you would need to do the following:

1.  include 'System/Service/SC2Ranks.php'
2.  instantiate a new System_Service_SC2Ranks object
    * the constructor takes two parameters: $options and $httpClient. $options requires you pass
      an 'appKey' or it will throw an exception. $httpClient is optional, but included if you have
      subclassed Zend_Http_Client on your own. Below is a robust options array:
    
    ```php
    $options = array(
        'appKey' => 'cstarleague.com',
        'httpClient' => array(
            'maxredirects' => 0,     // redirects to follow before failing
            'timeout' => 60,         // maximum time to wait for a response - See a.
            'keepalive' => true,     // See b.
        ),
        'charactersPerQuery' => 50,  // The default value
    );
    ```

3.  Pass an array of an array of character markers to the getMassBaseTeams function

    * The function accepts the following marker formats:
   
    ```php     
    $characters = array(
        array('uri' => 'http://us.battle.net/sc2/en/profile/902213/1/VPSuppy/'),  // b.net URI
        array('uri' => 'http://sc2ranks.com/us/2290485/heavonearth'),             // sc2r URI
        array('name' => 'vtgiX', 'code' => 862, 'region' => 'us'),                // name + code
    );
    ```
    
    You may include both uri + name and code for redundancy. If you do not include a region, the
    default region will be used (us). You can also pass a default region as the second parameter
    to getMassBaseTeams
        
    * The third and forth paramters to getMassBaseTeams are $bracketType and $isRandom.
      $bracketType is an int indicating the bracket requested - 1 stands for 1vs1, 2 for 2vs2, and
      so on. $isRandom is an int indicating if you are querying for pre-arranged teams in
      the bracket. Note that for the 1vs1 bracket, teams can never be pre-arranged

4.  Work on the return array of System_Service_SC2Ranks_Character. This convenience class exposes
    the following methods:

    * isValid:         returns true if the query returned character + team information
    * getName
    * getDivision
    * getDivisionRank
    * getLeague
    * getPoints
    * getWins
    * getLosses
       
    Please browse the source code for more information.
       
    * It is better to perform more queries with fewer charactersPerQuery than less queries with the
      maximum number of charactersPerQuery. You are less likely to exceed the default timeout value
      of 60 seconds and can better guarantee the API will return information for the characters you
      query.
       
    * When performing several requests to the same host, it is highly recommended to enable the
      'keepalive' configuration flag. This way, if the server supports keep-alive connections, the
      connection to the server will only be closed once all requests are done and the Client object
      is destroyed. This prevents the overhead of opening and closing TCP connections to the
      server.

Todo
----

* Expose a query for character searching
* Expose a method for scrapping historical data (by season) for inactive player accounts