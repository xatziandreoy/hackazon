<?php
/**
 * Created with IntelliJ IDEA by Nick Chervyakov.
 * User: Nikolay Chervyakov 
 * Date: 22.04.2015
 * Time: 11:47
  */



namespace App\Test;


class RESTWebTestCase extends WebTestCase
{
    protected $token;

    protected $secretFile;

    protected $secretContent;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->secretFile = str_replace(DIRECTORY_SEPARATOR, '/', realpath(__DIR__.'/data/hidden.txt'));
        $this->secretContent = trim(file_get_contents($this->secretFile));
    }

    public function fetchToken()
    {
        $this->client->setHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->password));
        $this->client->request('GET', '/api/auth');

        $data = json_decode(''.$this->getResponse()->getContent(), true);
        $this->token = $data;
    }

    /**
     * @param $method
     * @param $uri
     * @param array $parameters
     * @param array $files
     * @param array $server
     * @param null $content
     * @param bool $changeHistory
     * @return array
     */
    public function apiRequest($method, $uri, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true)
    {
        if (!$this->token) {
            $this->fetchToken();
        }

        $this->client->setHeader('Authorization', 'Token ' . $this->token);
        $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        return json_decode(''.$this->getResponse()->getContent(), true);
    }

     // Test 1: Verify if secret file content is loaded properly
    public function testSecretFileContent()
    {
        $this->assertNotEmpty(
            $this->secretContent, 
            "Secret file content should not be empty."
        );
    }

    // Test 2: Verify that token is fetched correctly
    public function testFetchToken()
    {
        $this->fetchToken();
        $this->assertNotEmpty(
            $this->token, 
            "Token should not be empty after fetchToken() call."
        );
    }

    // Test 3: Test API request functionality
    public function testApiRequest()
    {
        $response = $this->apiRequest('GET', '/api/test');
        $this->assertIsArray(
            $response, 
            "API response should be a valid JSON array."
        );

        $this->assertArrayHasKey(
            'status', 
            $response, 
            "Response array must contain a 'status' key."
        );
    }

    // Test 4: Test XML payload generation
    public function testXMLExternalEntityPayload()
    {
        $payload = $this->getXMLExternalEntityPayload();
        $this->assertStringContainsString(
            "<!DOCTYPE roottag", 
            $payload, 
            "Payload should contain a valid XML DOCTYPE."
        );

        $this->assertStringContainsString(
            "file:///", 
            $payload, 
            "Payload should reference a file URL."
        );
    }


    /**
     * @return string
     */
    protected function getXMLExternalEntityPayload()
    {
        $filename = $this->secretFile;
        $xmlString = <<<EOL
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE roottag [<!ENTITY goodies SYSTEM "file:///$filename">]>
<roottag>&goodies;</roottag>
EOL;
        return trim($xmlString);
    }
}