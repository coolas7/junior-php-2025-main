<?php

namespace App\Service;

use App\Entity\IpInfo;
use App\Service\BlacklistService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

/**
 * Service to handle IP information retrieval and storage.
 */
class IpInfoService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $http,
        private string $ipstackKey,
        private BlacklistService $blacklistService
    ) {
    }

    /**
     * Get IP information, fetching from API if not in DB or outdated.
     *
     * @param string $ip
     * @return IpInfo
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getIpInfo(string $ip): IpInfo
    {
        // Validate IP address format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP address format: $ip");
        }

        $repo = $this->em->getRepository(IpInfo::class);
        $ipInfo = $repo->findOneBy(['ip' => $ip]);

        // Check if the IP is blacklisted
        if ($ipInfo && $this->blacklistService->isBlacklisted($ipInfo)) {
            throw new \RuntimeException("This IP address is blacklisted");
        }

        // Check if the record exists and is not outdated (24 hours)
        $now = new \DateTime();
        $threshold = (clone $now)->modify('-24 hours');

        // If the record exists and is not outdated, return it
        if ($ipInfo && $ipInfo->getDate() >= $threshold) {
            return $ipInfo;
        }

        // Fetch new data from the API
        try {
            $response = $this->http->request(
                'GET',
                "http://api.ipstack.com/{$ip}?access_key={$this->ipstackKey}"
            );

            $data = $response->toArray(false);

            // Handle API error response
            if (isset($data['success']) && $data['success'] === false) {
                throw new \RuntimeException('IPStack API error: ' . ($data['error']['info'] ?? 'IP not found'));
            }
        } catch (ClientExceptionInterface | TransportExceptionInterface | ServerExceptionInterface $e) {
            throw new \RuntimeException('Failed to fetch IP info: ' . $e->getMessage());
        }

        // If there is no record, create a new one
        if (!$ipInfo) {
            $ipInfo = new IpInfo();
            $ipInfo->setIp($ip);
        }

        // Update fields with data from the API
        $ipInfo->setType($data['type'] ?? null);
        $ipInfo->setContinentCode($data['continent_code'] ?? null);
        $ipInfo->setContinentName($data['continent_name'] ?? null);
        $ipInfo->setCountryCode($data['country_code'] ?? null);
        $ipInfo->setCountryName($data['country_name'] ?? null);
        $ipInfo->setRegionCode($data['region_code'] ?? null);
        $ipInfo->setRegionName($data['region_name'] ?? null);
        $ipInfo->setCity($data['city'] ?? null);
        $ipInfo->setZip($data['zip'] ?? null);
        $ipInfo->setLatitude($data['latitude'] ?? null);
        $ipInfo->setLongitude($data['longitude'] ?? null);
        $ipInfo->setDate(new \DateTime());

        $this->em->persist($ipInfo);
        $this->em->flush();

        return $ipInfo;
    }

    /**
     * Delete an IP from the database.
     *
     * @param string $ip
     * @throws \RuntimeException if IP not found
     */
    public function deleteIp(string $ip): void
    {
        $repo = $this->em->getRepository(IpInfo::class);
        $ipInfo = $repo->findOneBy(['ip' => $ip]);

        // Throw exception if IP not found in the database
        if (!$ipInfo) {
            throw new \RuntimeException("IP address $ip not found in the database.");
        }

        $this->em->remove($ipInfo);
        $this->em->flush();
    }
}
