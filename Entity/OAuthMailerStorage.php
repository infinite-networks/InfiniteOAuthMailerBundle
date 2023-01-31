<?php declare(strict_types=1);

namespace Infinite\OAuthMailerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class OAuthMailerStorage
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $id = 1;

    /**
     * @ORM\Column(type="string", length=254)
     */
    private string $username = '';

    /**
     * @ORM\Column(type="text")
     */
    private string $accessToken = '';

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $accessTokenExpires;

    /**
     * @ORM\Column(type="text")
     */
    private string $refreshToken = '';

    public function __construct()
    {
        $this->accessTokenExpires = new \DateTime;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessTokenExpires(): \DateTime
    {
        return $this->accessTokenExpires;
    }

    public function setAccessTokenExpires(\DateTime $accessTokenExpires): void
    {
        $this->accessTokenExpires = $accessTokenExpires;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }
}
