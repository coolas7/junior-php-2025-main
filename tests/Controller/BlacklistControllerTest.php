<?php

namespace App\Tests\Controller;

use App\Entity\IpInfo;
use App\Service\BlacklistService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlacklistControllerTest extends WebTestCase
{
    private function mockBlacklistService(callable $configure): void
    {
        /** @var ContainerInterface $container */
        $container = static::getContainer();

        $mock = $this->createMock(BlacklistService::class);
        $configure($mock);

        $container->set(BlacklistService::class, $mock);
    }

    private function mockEntityManagerWithIp(?IpInfo $ipInfo): void
    {
        /** @var ContainerInterface $container */
        $container = static::getContainer();

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn($ipInfo);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);

        $container->set(EntityManagerInterface::class, $emMock);
    }

    public function testAddIpSuccess()
    {
        $client = static::createClient();

        $ipInfo = (new IpInfo())->setIp('134.201.250.155');

        $this->mockEntityManagerWithIp($ipInfo);

        $this->mockBlacklistService(function ($mock) use ($ipInfo) {
            $mock->method('isBlacklisted')->with($ipInfo)->willReturn(false);
            $mock->expects($this->once())->method('addToBlacklist')->with($ipInfo);
        });

        $client->request(
            'POST',
            '/api/blacklist',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ip' => '134.201.250.155'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('IP 134.201.250.155 added to blacklist', $data['message']);
    }

    public function testAddIpNotFound()
    {
        $client = static::createClient();

        $this->mockEntityManagerWithIp(null);

        $this->mockBlacklistService(function ($mock) {
            $mock->method('isBlacklisted')->willReturn(false);
        });

        $client->request(
            'POST',
            '/api/blacklist',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ip' => '8.8.8.8'])
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('IP not found in database', $data['error']);
    }

    public function testAddIpAlreadyBlacklisted()
    {
        $client = static::createClient();

        $ipInfo = (new IpInfo())->setIp('134.201.250.155');
        $this->mockEntityManagerWithIp($ipInfo);

        $this->mockBlacklistService(function ($mock) use ($ipInfo) {
            $mock->method('isBlacklisted')->with($ipInfo)->willReturn(true);
        });

        $client->request(
            'POST',
            '/api/blacklist',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ip' => '134.201.250.155'])
        );

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already in blacklist', $data['error']);
    }

    public function testBulkAdd()
    {
        $client = static::createClient();

        $ipInfo1 = (new IpInfo())->setIp('134.201.250.155');
        $ipInfo2 = (new IpInfo())->setIp('131.101.150.139');

        $this->mockEntityManagerWithIpCallback(function ($ip) use ($ipInfo1, $ipInfo2) {
            if ($ip === '134.201.250.155') {
                return $ipInfo1;
            }
            if ($ip === '131.101.150.139') {
                return $ipInfo2;
            }
            return null;
        });

        $this->mockBlacklistService(function ($mock) {
            $mock->method('isBlacklisted')->willReturn(false);
            $mock->method('addToBlacklist');
        });

        $client->request(
            'POST',
            '/api/blacklist/bulk',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ips' => ['134.201.250.155', '131.101.150.139', '8.8.8.8']])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertContains('134.201.250.155', $data['added']);
        $this->assertContains('131.101.150.139', $data['added']);
        $this->assertContains('8.8.8.8', $data['skipped']['not_found']);
    }

    public function testRemoveIpSuccess()
    {
        $client = static::createClient();

        $ipInfo = (new IpInfo())->setIp('134.201.250.155');
        $this->mockEntityManagerWithIp($ipInfo);

        $this->mockBlacklistService(function ($mock) use ($ipInfo) {
            $mock->method('isBlacklisted')->with($ipInfo)->willReturn(true);
            $mock->expects($this->once())->method('removeFromBlacklist')->with($ipInfo);
        });

        $client->request(
            'DELETE',
            '/api/blacklist/134.201.250.155'
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('IP 134.201.250.155 removed from blacklist', $data['message']);
    }

    public function testRemoveIpNotFound()
    {
        $client = static::createClient();

        $this->mockEntityManagerWithIp(null);

        $this->mockBlacklistService(function ($mock) {
            $mock->method('isBlacklisted')->willReturn(false);
        });

        $client->request(
            'DELETE',
            '/api/blacklist/8.8.8.8'
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('IP not found in database', $data['error']);
    }

    public function testBulkRemove()
    {
        $client = static::createClient();

        $ipInfo1 = (new IpInfo())->setIp('134.201.250.155');
        $ipInfo2 = (new IpInfo())->setIp('131.101.150.139');

        $this->mockEntityManagerWithIpCallback(function ($ip) use ($ipInfo1, $ipInfo2) {
            if ($ip === '134.201.250.155') {
                return $ipInfo1;
            }
            if ($ip === '131.101.150.139') {
                return $ipInfo2;
            }
            if ($ip === '8.8.8.8') {
                return new IpInfo()->setIp('8.8.8.8');
            }
            return null;
        });

        $this->mockBlacklistService(function ($mock) {
            $mock->method('isBlacklisted')->willReturnCallback(function ($ipInfo) {
                if ($ipInfo->getIp() === '134.201.250.155') {
                    return true;
                }
                return false;
            });
            $mock->method('removeFromBlacklist');
        });

        $client->request(
            'POST',
            '/api/blacklist/bulk-delete',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ips' => ['134.201.250.155', '131.101.150.139', '8.8.8.8']])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertContains('134.201.250.155', $data['removed']);
        $this->assertContains('131.101.150.139', $data['skipped']['not_in_blacklist']);
        $this->assertContains('8.8.8.8', $data['skipped']['not_in_blacklist']);
    }

    private function mockEntityManagerWithIpCallback(callable $callback): void
    {
        /** @var ContainerInterface $container */
        $container = static::getContainer();

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturnCallback(function ($criteria) use ($callback) {
            return $callback($criteria['ip'] ?? null);
        });

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);

        $container->set(EntityManagerInterface::class, $emMock);
    }
}
