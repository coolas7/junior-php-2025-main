<?php

namespace App\Entity;

use App\Repository\IpInfoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IpInfoRepository::class)]
class IpInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ip = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $continent_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $continent_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\OneToOne(mappedBy: 'ip', cascade: ['persist', 'remove'])]
    private ?BlacklistedIp $blacklistedIp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getContinentCode(): ?string
    {
        return $this->continent_code;
    }

    public function setContinentCode(?string $continent_code): static
    {
        $this->continent_code = $continent_code;

        return $this;
    }

    public function getContinentName(): ?string
    {
        return $this->continent_name;
    }

    public function setContinentName(?string $continent_name): static
    {
        $this->continent_name = $continent_name;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->country_code;
    }

    public function setCountryCode(?string $country_code): static
    {
        $this->country_code = $country_code;

        return $this;
    }

    public function getCountryName(): ?string
    {
        return $this->country_name;
    }

    public function setCountryName(?string $country_name): static
    {
        $this->country_name = $country_name;

        return $this;
    }

    public function getRegionCode(): ?string
    {
        return $this->region_code;
    }

    public function setRegionCode(?string $region_code): static
    {
        $this->region_code = $region_code;

        return $this;
    }

    public function getRegionName(): ?string
    {
        return $this->region_name;
    }

    public function setRegionName(?string $region_name): static
    {
        $this->region_name = $region_name;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getBlacklistedIp(): ?BlacklistedIp
    {
        return $this->BlacklistedIp;
    }

    public function setBlacklistedIp(?BlacklistedIp $BlacklistedIp): static
    {
        // unset the owning side of the relation if necessary
        if ($BlacklistedIp === null && $this->BlacklistedIp !== null) {
            $this->BlacklistedIp->setIpId(null);
        }

        // set the owning side of the relation if necessary
        if ($BlacklistedIp !== null && $BlacklistedIp->getIpId() !== $this) {
            $BlacklistedIp->setIpId($this);
        }

        $this->BlacklistedIp = $BlacklistedIp;

        return $this;
    }
}
