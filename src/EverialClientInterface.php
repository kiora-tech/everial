<?php

namespace Kiora;

use Kiora\Exception\AuthException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface EverialClientInterface
{
    public function auth(): ResponseInterface;

    public function serialize(\SplFileObject $file): ResponseInterface;

    public function recognize(\SplFileObject $file): ResponseInterface;

    public function analyse(\SplFileObject $file, string $radId, string $dbId): ResponseInterface;
}
