<?php

require_once 'Zend/Http/Response.php';

require_once 'System/Service/SC2Ranks/Response.php';

class ResponseTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorWithProperResponse()
    {
        $this->assertFileExists('Tests/data/proper_response.ser');
        $response = unserialize(file_get_contents('Tests/data/proper_response.ser'));
        $this->assertInstanceOf('Zend_Http_Response', $response);
        $this->assertEquals(200, $response->getStatus());
        
        $responseWrapper = new System_Service_SC2Ranks_Response($response);
        $this->assertEquals(true, $responseWrapper->isValid());
        $this->assertEquals(true, $responseWrapper->hasCharacter('VPSuppy'));
        $this->assertEquals(true, $responseWrapper->hasCharacter('JasonX'));
    }
    
    public function testConstructorWithApiErrorInResponse()
    {
        $this->assertFileExists('Tests/data/errored_response.ser');
        $response = unserialize(file_get_contents('Tests/data/errored_response.ser'));
        $this->assertInstanceOf('Zend_Http_Response', $response);
        $this->assertEquals(200, $response->getStatus());
        
        $body = Zend_Json::decode($response->getBody());
        $this->assertArrayHasKey('error', $body);
        $this->assertEquals('no_characters', $body['error']); 
    }
    
    public function testConstructorWithInValidReponse()
    {
        $this->assertFileExists('Tests/data/404_response.ser');
        $response = unserialize(file_get_contents('Tests/data/404_response.ser'));
        $this->assertInstanceOf('Zend_Http_Response', $response);
        $this->assertEquals(404, $response->getStatus());
        
        $responseWrapper = new System_Service_SC2Ranks_Response($response);
        $this->assertEquals(false, $responseWrapper->isValid());
        $this->assertEquals('Query returned status 404', $responseWrapper->getErrorMessage());
    }
    
    public function testConstructorForErrorString()
    {
        $responseWrapper = new System_Service_SC2Ranks_Response('Unable to read response, or response is empty');
        $this->assertEquals(false, $responseWrapper->isValid());
        $this->assertEquals('Unable to read response, or response is empty', $responseWrapper->getErrorMessage());
    }
    
    /**
     * @expectedException System_Service_SC2Ranks_Response_Exception
     * @expectedExceptionMessage The response passed could not be wrapped
     */
    public function testExceptionThrownFromConstructor()
    {
        $responseWrapper = new System_Service_SC2Ranks_Response(array());
    }
    
    public function testSettersAndGetters()
    {
        $this->assertFileExists('Tests/data/proper_response.ser');
        $response = unserialize(file_get_contents('Tests/data/proper_response.ser'));
        $this->assertInstanceOf('Zend_Http_Response', $response);
        
        $body = Zend_Json::decode($response->getBody());
        $responseWrapper = new System_Service_SC2Ranks_Response($response);
        
        foreach($body as $character)
        {
            if(isset($character['name']))
            {
                $this->assertEquals(true, $responseWrapper->hasCharacter($character['name']));
                $this->assertEquals($character, $responseWrapper->getCharacter($character['name']));
            }
        }
        
        $responseWrapper->setStatus(true);
        $this->assertEquals(true, $responseWrapper->isValid());
        
        $responseWrapper->setStatus(false);
        $this->assertEquals(false, $responseWrapper->isValid());
        
        $responseWrapper->setErrorMessage('HELLO');
        $this->assertEquals('HELLO', $responseWrapper->getErrorMessage());
    }
}
