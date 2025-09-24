<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\BlacklistService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\IpInfo;
use App\Entity\BlacklistedIp;

/**
 * @coversDefaultClass \App\Service\BlacklistService
 */
class BlacklistServiceTest extends TestCase
{
    /**
     * @covers ::addToBlacklist
     */
    public function testAddToBlacklistPersistsEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $ipInfo = $this->createMock(IpInfo::class);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(BlacklistedIp::class));
        $em->expects($this->once())
            ->method('flush');

        $service = new BlacklistService($em);
        $result = $service->addToBlacklist($ipInfo);

        $this->assertInstanceOf(BlacklistedIp::class, $result);
        $this->assertSame($ipInfo, $result->getIpId());
        $this->assertInstanceOf(\DateTime::class, $result->getCreatedAt());
    }

    /**
     * @covers ::removeFromBlacklist
     */
    public function testRemoveFromBlacklistRemovesEntity()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $ipInfo = $this->createMock(IpInfo::class);
        $blacklistedIp = $this->createMock(BlacklistedIp::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['ip' => $ipInfo])->willReturn($blacklistedIp);

        $em->method('getRepository')->with(BlacklistedIp::class)->willReturn($repo);

        $em->expects($this->once())
            ->method('remove')
            ->with($blacklistedIp);
        $em->expects($this->once())
            ->method('flush');

        $service = new BlacklistService($em);
        $service->removeFromBlacklist($ipInfo);
        $this->assertTrue(true); // If no exception, test passes
    }

    /**
     * @covers ::removeFromBlacklist
     */
    public function testRemoveFromBlacklistThrowsIfNotFound()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $ipInfo = $this->createMock(IpInfo::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['ip' => $ipInfo])->willReturn(null);

        $em->method('getRepository')->with(BlacklistedIp::class)->willReturn($repo);

        $service = new BlacklistService($em);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('IP not in blacklist');
        $service->removeFromBlacklist($ipInfo);
    }

    /**
     * @covers ::isBlacklisted
     */
    public function testIsBlacklistedReturnsTrueIfFound()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $ipInfo = $this->createMock(IpInfo::class);
        $blacklistedIp = $this->createMock(BlacklistedIp::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['ip' => $ipInfo])->willReturn($blacklistedIp);

        $em->method('getRepository')->with(BlacklistedIp::class)->willReturn($repo);

        $service = new BlacklistService($em);
        $this->assertTrue($service->isBlacklisted($ipInfo));
    }

    /**
     * @covers ::isBlacklisted
     */
    public function testIsBlacklistedReturnsFalseIfNotFound()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $ipInfo = $this->createMock(IpInfo::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->with(['ip' => $ipInfo])->willReturn(null);

        $em->method('getRepository')->with(BlacklistedIp::class)->willReturn($repo);

        $service = new BlacklistService($em);
        $this->assertFalse($service->isBlacklisted($ipInfo));
    }
}
