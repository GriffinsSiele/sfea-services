<?php

declare(strict_types=1);

namespace App\Test;

use App\Kernel;
use App\Model\Request;
use App\Model\Response;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractPluginTest extends WebTestCase
{
    //    protected Client $client;
    protected KernelBrowser $client;
    protected LoggerInterface $logger;
    protected string $accessToken;
    protected string $host;

    protected function init(): void
    {
        //        self::bootKernel();

        //        $this->client = self::getContainer()->get('app.guzzle');
        $this->client = self::createClient();
        $this->logger = self::getContainer()->get('app.logger');
        //        $this->host = \getenv('APP_HOST') ?: 'http://nginx:80';

        /* @see Kernel::getInstance() */
        $GLOBALS['app'] = static::$kernel;
    }

    protected function authenticate(): void
    {
        $start = \microtime(true);
        $this->logger->debug('authenticate');

        $username = 'sk';
        $password = 'L0sVq$xA';

        // authorization
        //        $response = $this->client->post(
        //            $this->host.'/api/v1/login',
        //            [
        //                RequestOptions::JSON => [
        //                    'username' => $username,
        //                    'password' => $password,
        //                ],
        //            ],
        //        );

        $this->client->request(
            SymfonyRequest::METHOD_POST,
            '/api/v1/login',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: \json_encode([
                'username' => $username,
                'password' => $password,
            ], \JSON_THROW_ON_ERROR),
        );

        //        self::assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
        //
        //        $body = $response->getBody()->__toString();
        //
        //        self::assertNotEmpty($body);

        self::assertResponseIsSuccessful();

        $body = $this->client->getResponse()->getContent();
        $response = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('token', $response);

        $this->accessToken = $response['token'];

        $this->logger->debug('authenticate success', [
            'duration_s' => \microtime(true) - $start,
        ]);
    }

    protected function post(Request $request): Response
    {
        $start = \microtime(true);
        $this->logger->debug('post request');

        /** @var SerializerInterface $serializer */
        $serializer = self::$kernel->getContainer()
            ->get('app.serializer');

        $serialized = $serializer->serialize($request, XmlEncoder::FORMAT, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            XmlEncoder::ROOT_NODE_NAME => 'Request',
        ]);

        //        $response = $this->client->post(
        //            $this->host . '/',
        //            [
        //                RequestOptions::HEADERS => [
        //                    'Authorization' => 'Bearer ' . $this->accessToken,
        //                    'Content-Type' => 'application/xml',
        //                    'Accept' => 'application/xml',
        //                ],
        //                RequestOptions::BODY => $serialized,
        //            ],
        //        );

        $this->client->request(
            SymfonyRequest::METHOD_POST,
            '/',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->accessToken,
                'CONTENT_TYPE' => 'application/xml',
                'ACCEPT' => 'application/xml',
            ],
            content: $serialized,
        );

        //        self::assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
        //
        //        $body = $response->getBody()->__toString();
        //
        //        self::assertNotEmpty($body);

        $this->assertResponseIsSuccessful();

        $body = $this->client->getResponse()->getContent();

        $response = $serializer->deserialize(
            $body,
            Response::class,
            XmlEncoder::FORMAT,
        );

        self::assertInstanceOf(Response::class, $response);

        $this->logger->debug('post request success', [
            'duration_s' => \microtime(true) - $start,
        ]);

        return $response;
    }
}
