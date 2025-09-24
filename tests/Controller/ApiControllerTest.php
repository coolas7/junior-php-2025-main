<?php

namespace App\Tests\Controller;

use App\Entity\IpInfo;
use App\Service\IpInfoService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApiControllerTest extends WebTestCase
{
    private function mockService(callable $configure): void
    {
        /** @var ContainerInterface $container */
        $container = static::getContainer();

        $mock = $this->createMock(IpInfoService::class);
        $configure($mock);

        $container->set(IpInfoService::class, $mock);
    }

    public function testHealthEndpoint()
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $this->assertStringContainsString('ok', $client->getResponse()->getContent());
    }

    public function testGetIpInfoValid()
    {
        $client = static::createClient();

        $ipInfo = (new IpInfo())
            ->setIp('134.201.250.155')
            ->setType('ipv4')
            ->setCountryName('United States');

        $this->mockService(function ($mock) use ($ipInfo) {
            $mock->method('getIpInfo')->willReturn($ipInfo);
        });

        $client->request('GET', '/api/ip/134.201.250.155');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('134.201.250.155', $data['ip']);
        $this->assertEquals('United States', $data['country_name']);
    }

    public function testGetIpInfoInvalidFormat()
    {
        $client = static::createClient();
        $client->request('GET', '/api/ip/invalid_ip');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Invalid IP address format', $data['error']);
    }

    public function testDeleteIpSuccess()
    {
        $client = static::createClient();

        $this->mockService(function ($mock) {
            $mock->expects($this->once())
                 ->method('deleteIp')
                 ->with('134.201.250.155');
        });

        $client->request('DELETE', '/api/ip/134.201.250.155');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertStringContainsString('deleted successfully', $data['message']);
    }

    public function testDeleteIpInvalidFormat()
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/ip/not-an-ip');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Invalid IP address format', $data['error']);
    }

    public function testBulkGetIpInfo()
    {
        $client = static::createClient();

        $ipInfo = (new IpInfo())->setIp('134.201.250.155')->setType('ipv4');

        $this->mockService(function ($mock) use ($ipInfo) {
            $mock->method('getIpInfo')->willReturn($ipInfo);
        });

        $client->request(
            'POST',
            '/api/ip/bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ips' => ['134.201.250.155', '8.8.8.8']])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('results', $data);
        $this->assertCount(2, $data['results']);
    }

    public function testBulkDelete()
    {
        $client = static::createClient();

        $this->mockService(function ($mock) {
            $mock->method('deleteIp');
        });

        $client->request(
            'POST',
            '/api/ip/bulk-delete',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ips' => ['134.201.250.155', 'invalid_ip']])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertContains('134.201.250.155', $data['deleted']);
        $this->assertNotEmpty($data['errors']);
    }
}
