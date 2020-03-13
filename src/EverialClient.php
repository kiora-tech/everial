<?php


namespace Kiora;


use Kiora\Exception\AuthException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EverialClient
{
    private const AUTH_URL = '/auth/realms/quota/protocol/openid-connect/token';
    private const SERIALIZE_URL = '/api/v1/serialize';
    private const RECOGNIZE_URL = '/api/v1/recognize';
    /**
     * @var HttpClientInterface
     */
    private $client;
    /**
     * @var string
     */
    private $everialAuthBasePath;
    /**
     * @var string
     */
    private $everialUsername;
    /**
     * @var string
     */
    private $everialPassword;
    /**
     * @var string
     */
    private $everialBasePath;


    public function __construct(
        HttpClientInterface $client,
        string $everialAuthBasePath,
        string $everialBasePath,
        string $everialUsername,
        string $everialPassword
    ) {
        $this->client = $client;
        $this->everialAuthBasePath = $everialAuthBasePath;
        $this->everialUsername = $everialUsername;
        $this->everialPassword = $everialPassword;
        $this->everialBasePath = $everialBasePath;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function auth(): ResponseInterface
    {
        return $this->client->request('POST', $this->everialAuthBasePath . static::AUTH_URL, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'username' => $this->everialUsername,
                    'password' => $this->everialPassword,
                    'grant_type' => 'password',
                    'client_id' => 'api',
                    'scope' => 'openid'
                ]
            ]
        );

    }

    /**
     * @param \SplFileObject $file
     * @return ResponseInterface
     * @throws AuthException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function serialize(\SplFileObject $file): ResponseInterface
    {
        try {
            $accessToken = $this->auth()->toArray()['access_token'] ?? null;
        } catch (HttpExceptionInterface|DecodingExceptionInterface $exception) {
            throw new AuthException('A problem with auth', 0, $exception);
        }


        $formFields = [
            'file' => DataPart::fromPath($file->getRealPath()),
        ];

        $formData = new FormDataPart($formFields);
        $headers = $formData->getPreparedHeaders()->toArray();
        $headers[] = 'Authorization: Bearer ' . $accessToken;

        return $this->client->request('POST', $this->everialBasePath . static::SERIALIZE_URL, [
                'headers' => $headers,
                'body' => $formData->bodyToIterable()
            ]
        );
    }

    public function reconize(\SplFileObject $file): ResponseInterface
    {
        try {
            $accessToken = $this->auth()->toArray()['access_token'] ?? null;
        } catch (HttpExceptionInterface|DecodingExceptionInterface $exception) {
            throw new AuthException('A problem with auth', 0, $exception);
        }


        $formFields = [
            'file' => DataPart::fromPath($file->getRealPath()),
        ];

        $formData = new FormDataPart($formFields);
        $headers = $formData->getPreparedHeaders()->toArray();
        $headers[] = 'Authorization: Bearer ' . $accessToken;

        return $this->client->request('POST', $this->everialBasePath . static::RECOGNIZE_URL, [
                'headers' => $headers,
                'body' => $formData->bodyToIterable()
            ]
        );
    }
}