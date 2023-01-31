<?php declare(strict_types=1);

namespace Infinite\OAuthMailerBundle\Repository;

use Infinite\OAuthMailerBundle\Entity\OAuthMailerStorage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * @method OAuthMailerStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method OAuthMailerStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method OAuthMailerStorage[]    findAll()
 */
class OAuthMailerStorageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthMailerStorage::class);
    }

    public function getData(): ?OAuthMailerStorage
    {
        return $this->findOneBy([]);
    }

    public function createOrUpdateDataFrom(AccessTokenInterface $accessToken): OAuthMailerStorage
    {
        $data = $this->getData();

        if (!$data) {
            $data = new OAuthMailerStorage;
        }

        $expiryDate = new \DateTime('@'.$accessToken->getExpires());
        $expiryDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $data->setAccessToken($accessToken->getToken());
        $data->setAccessTokenExpires($expiryDate);

        if ($accessToken->getRefreshToken()) {
            $data->setRefreshToken($accessToken->getRefreshToken());
        }

        if (!$data->getRefreshToken()) {
            throw new \RuntimeException('No refresh token');
        }

        return $data;
    }

    public function remove(OAuthMailerStorage $data, bool $flush = false): void
    {
        $this->_em->remove($data);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function save(OAuthMailerStorage $data): void
    {
        $this->_em->persist($data);
        $this->_em->flush();
    }
}
