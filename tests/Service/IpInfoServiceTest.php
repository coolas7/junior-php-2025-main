<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\IpInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\BlacklistService;
use App\Entity\IpInfo;

/**
 * @coversDefaultClass \App\Service\IpInfoService
 */
class IpInfoServiceTest extends TestCase
{
    /**
    * @covers ::getIpInfo
    */
    public function testGetIpInfoThrowsExceptionForInvalidIp()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $http = $this->createMock(HttpClientInterface::class);
        $blacklistService = $this->createMock(BlacklistService::class);

        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $this->expectException(\InvalidArgumentException::class);
        $service->getIpInfo('invalid_ip');
    }

    /**
    * @covers ::getIpInfo
    */
    public function testGetIpInfoThrowsExceptionIfBlacklisted()
    {
        $ipInfo = new IpInfo();
        $ipInfo->setIp('134.201.250.155');
        $ipInfo->setDate(new \DateTime());

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($ipInfo);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $http = $this->createMock(HttpClientInterface::class);
        $blacklistService = $this->createMock(BlacklistService::class);
        $blacklistService->method('isBlacklisted')->willReturn(true);

        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This IP address is blacklisted');
        $service->getIpInfo('134.201.250.155');
    }

    /**
    * @covers ::getIpInfo
    */
    public function testGetIpInfoReturnsCachedIpInfoIfNotOutdated()
    {
        $ipInfo = new IpInfo();
        $ipInfo->setIp('134.201.250.155');
        $ipInfo->setDate(new \DateTime());

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($ipInfo);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $http = $this->createMock(HttpClientInterface::class);
        $blacklistService = $this->createMock(BlacklistService::class);
        $blacklistService->method('isBlacklisted')->willReturn(false);

        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $result = $service->getIpInfo('134.201.250.155');
        $this->assertSame($ipInfo, $result);
    }

    /**
    * @covers ::getIpInfo
    */
    public function testGetIpInfoFetchesFromApiIfOutdated()
    {
        $ipInfo = new IpInfo();
        $ipInfo->setIp('134.201.250.155');
        $ipInfo->setDate((new \DateTime())->modify('-25 hours'));

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($ipInfo);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $httpResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([
            'type' => 'ipv4',
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'country_code' => 'US',
            'country_name' => 'United States',
            'region_code' => 'CA',
            'region_name' => 'California',
            'city' => 'Los Angeles',
            'zip' => '90001',
            'latitude' => 34.05223,
            'longitude' => -118.24368,
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($httpResponse);

        $blacklistService = $this->createMock(BlacklistService::class);
        $blacklistService->method('isBlacklisted')->willReturn(false);

        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $result = $service->getIpInfo('134.201.250.155');
        $this->assertInstanceOf(IpInfo::class, $result);
        $this->assertEquals('ipv4', $result->getType());
        $this->assertEquals('NA', $result->getContinentCode());
        $this->assertEquals('North America', $result->getContinentName());
        $this->assertEquals('US', $result->getCountryCode());
        $this->assertEquals('United States', $result->getCountryName());
        $this->assertEquals('CA', $result->getRegionCode());
        $this->assertEquals('California', $result->getRegionName());
        $this->assertEquals('Los Angeles', $result->getCity());
        $this->assertEquals('90001', $result->getZip());
        $this->assertEquals(34.05223, $result->getLatitude());
        $this->assertEquals(-118.24368, $result->getLongitude());
    }

    /**
    * @covers ::getIpInfo
    */
    public function testGetIpInfoThrowsExceptionOnApiError()
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $httpResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $httpResponse->method('toArray')->willReturn([
            'success' => false,
            'error' => ['info' => 'IP not found']
        ]);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('request')->willReturn($httpResponse);

        $blacklistService = $this->createMock(BlacklistService::class);
        $blacklistService->method('isBlacklisted')->willReturn(false);

        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IPStack API error: IP not found');
        $service->getIpInfo('134.201.250.155');
    }

    /**
     * @covers ::deleteIp
     */
    public function testDeleteIpRemovesExistingIp()
    {
        $ip = '134.201.250.155';

        $ipInfo = new IpInfo();
        $ipInfo->setIp($ip);

        $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findOneBy')->with(['ip' => $ip])->willReturn($ipInfo);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $em->expects($this->once())->method('remove')->with($ipInfo);
        $em->expects($this->once())->method('flush');

        $http = $this->createMock(\Symfony\Contracts\HttpClient\HttpClientInterface::class);
        $blacklistService = $this->createMock(\App\Service\BlacklistService::class);
        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $service->deleteIp($ip);
    }

    /**
     * @covers ::deleteIp
     */
    public function testDeleteIpThrowsExceptionIfIpNotFound()
    {
        $ip = '134.201.250.155';

        $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repo->method('findOneBy')->with(['ip' => $ip])->willReturn(null);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $http = $this->createMock(\Symfony\Contracts\HttpClient\HttpClientInterface::class);
        $blacklistService = $this->createMock(\App\Service\BlacklistService::class);
        $service = new IpInfoService($em, $http, 'dummy_key', $blacklistService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("IP address $ip not found in the database.");

        $service->deleteIp($ip);
    }
}
