<?php

namespace App\Service;

use App\Entity\BlacklistedIp;
use App\Entity\IpInfo;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to manage blacklisted IPs.
 */
class BlacklistService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function addToBlacklist(IpInfo $ipInfo): BlacklistedIp
    {
        $blacklist = new BlacklistedIp();
        $blacklist->setIpId($ipInfo);
        $blacklist->setCreatedAt(new \DateTime());

        $this->em->persist($blacklist);
        $this->em->flush();

        return $blacklist;
    }

    public function removeFromBlacklist(IpInfo $ipInfo, bool $flush = true): void
    {
        $repo = $this->em->getRepository(BlacklistedIp::class);
        $blacklist = $repo->findOneBy(['ip' => $ipInfo]);

        if (!$blacklist) {
            throw new \RuntimeException("IP not in blacklist");
        }

        $this->em->remove($blacklist);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function isBlacklisted(IpInfo $ipInfo): bool
    {
        $repo = $this->em->getRepository(BlacklistedIp::class);
        return (bool) $repo->findOneBy(['ip' => $ipInfo]);
    }
}
