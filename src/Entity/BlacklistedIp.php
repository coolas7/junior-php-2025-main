<?php

namespace App\Entity;

use App\Repository\BlacklistedIpRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlacklistedIpRepository::class)]
class BlacklistedIp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'blacklistedIp')]
    #[ORM\JoinColumn(name: "ip_id", referencedColumnName: "id")]
    private ?IpInfo $ip = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIpId(): ?IpInfo
    {
        return $this->ip;
    }

    public function setIpId(?IpInfo $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
