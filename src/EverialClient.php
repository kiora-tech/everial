<?php


namespace Kiora;


use Kiora\Exception\AuthException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
     * @return ResponseInterface
     * @throws TransportExceptionInterface
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
     * @throws TransportExceptionInterface
     */
    public function serialize(\SplFileObject $file): ResponseInterface
    {
        return $this->callWithFile($file, static::SERIALIZE_URL);
    }

    /**
     * @param \SplFileObject $file
     * @return ResponseInterface
     * @throws AuthException
     * @throws TransportExceptionInterface
     */
    public function recognize(\SplFileObject $file): ResponseInterface
    {
        return $this->callWithFile($file, static::RECOGNIZE_URL);
    }

    /**
     * Base call
     * @param \SplFileObject $file
     * @param string $path
     * @return ResponseInterface
     * @throws AuthException
     * @throws TransportExceptionInterface
     */
    private function callWithFile(\SplFileObject $file, string $path): ResponseInterface
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE); 
        $mimeType = $finfo->file($file->getRealPath());
        $formFields = [
            'file' => DataPart::fromPath($file->getRealPath(), null, $mimeType),
        ];

        $formData = new FormDataPart($formFields);
        $headers = $formData->getPreparedHeaders()->toArray();
        $headers[] = 'Authorization: Bearer ' . $this->getAccessToken();

        return $this->client->request('POST', $this->everialBasePath . $path, [
                'headers' => $headers,
                'body' => $formData->bodyToIterable()
            ]
        );
    }

    /**
     * Get the access token
     *
     * @return string
     * @throws AuthException
     * @throws TransportExceptionInterface
     */
    private function getAccessToken(): ?string
    {
        try {
            return $this->auth()->toArray()['access_token'] ?? null;
        } catch (HttpExceptionInterface|DecodingExceptionInterface $exception) {
            throw new AuthException('A problem with auth', 0, $exception);
        }
    }
}