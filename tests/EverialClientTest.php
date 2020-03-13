<?php


namespace Tests;


use Kiora\EverialClient;
use Kiora\Exception\AuthException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EverialClientTest extends TestCase
{
    public function testAuth()
    {
        $httpClient = $this->createClientMock(1);

        $client = $this->createEverialClient($httpClient);

        $this->assertInstanceOf(ResponseInterface::class, $client->auth());
    }

    public function testSerialize()
    {
        $httpClient = $this->createClientMock(2);

        $client = $this->createEverialClient($httpClient);

        $this->assertInstanceOf(ResponseInterface::class, $client->serialize(new \SplFileObject(__FILE__)));
    }

    public function testReconize()
    {
        $httpClient = $this->createClientMock(2);

        $client = $this->createEverialClient($httpClient);

        $this->assertInstanceOf(ResponseInterface::class, $client->reconize(new \SplFileObject(__FILE__)));
    }

    public function testSerializeFaildByAuht()
    {
        $response = $this->createResponseMock();
        $response->method('toArray')
            ->willThrowException(new JsonException());

        $httpClient = $this->createClientMock(1, $response);

        $client = $this->createEverialClient($httpClient);

        $this->expectException(AuthException::class);
        $this->assertInstanceOf(ResponseInterface::class, $client->serialize(new \SplFileObject(__FILE__)));
    }

    public function testAuthWithLogin()
    {
        $client = $this->createEverialClient(new CurlHttpClient(), getenv('EVERIAL_USERNAME'),
            getenv('EVERIAL_PASSWORD'));
        $response = $client->auth();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertArrayHasKey('access_token', $response->toArray());
    }

    public function testAuthWithWrongLogin()
    {
        $client = $this->createEverialClient(new CurlHttpClient(), 'foo', 'bar');
        $responce = $client->auth();

        $this->expectException(ServerException::class);
        $this->assertInstanceOf(ResponseInterface::class, $responce);
        $this->assertArrayHasKey('access_token', $responce->toArray());
    }

    public function testSerializeWithFile()
    {
        $client = $this->createEverialClient(new CurlHttpClient(), getenv('EVERIAL_USERNAME'),
            getenv('EVERIAL_PASSWORD'));
        $response = $client->serialize(new \SplFileObject(__DIR__ . '/Resources/ID.jpeg'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertArrayHasKey('status', $response->toArray());
    }

    public function testReconizeWithFile()
    {
        $client = $this->createEverialClient(new CurlHttpClient(), getenv('EVERIAL_USERNAME'),
            getenv('EVERIAL_PASSWORD'));
        $response = $client->reconize(new \SplFileObject(__DIR__ . '/Resources/ID.jpeg'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertArrayHasKey('radId', $response->toArray());
    }

    private function createEverialClient($httpClient, $username = '', $password = '')
    {
        return new EverialClient(
            $httpClient,
            'https://auth.doclab.everial.com',
            'https://radial.doclab.everial.com',
            $username,
            $password
        );
    }


    /**
     * @param $expects
     * @return \PHPUnit\Framework\MockObject\Builder\InvocationMocker|HttpClientInterface
     */
    private function createClientMock($expects, $response = null)
    {
        $mock = $this->createMock(HttpClientInterface::class);
        $mock->expects($this->exactly($expects))
            ->method('request')
            ->with($this->equalTo('POST'), $this->isType('string'), $this->isType('array'))
            ->willReturn($response ?: $this->createResponseMock());

        return $mock;
    }

    private function createResponseMock()
    {
        return $this->createMock(ResponseInterface::class);
    }

}