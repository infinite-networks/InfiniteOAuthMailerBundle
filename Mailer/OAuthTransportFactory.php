<?php declare(strict_types=1);

namespace Infinite\OAuthMailerBundle\Mailer;

use Infinite\OAuthMailerBundle\Repository\OAuthMailerStorageRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class OAuthTransportFactory extends AbstractTransportFactory
{
    private ?OAuthManager $manager = null;

    public function __construct(
        ?EventDispatcherInterface $dispatcher,
        ?LoggerInterface $logger,
        private OAuthMailerStorageRepository $repository,
        private string $fallbackDsn,
        private Transport $transport,
    ) {
        parent::__construct($dispatcher, null, $logger);
    }

    public function setManager(OAuthManager $manager)
    {
        $this->manager = $manager;
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $fallbackTransport = $this->transport->fromDsn($this->fallbackDsn);

        return new OAuthTransport(
            $dsn->getHost(),
            $this->repository->getData()?->getUsername(),
            $this->dispatcher,
            $this->logger,
            $fallbackTransport,
            $this->manager,
        );
    }

    public static function getOauth2Settings(Dsn $dsn): array
    {
        return [
            'clientId'                => $dsn->getOption('clientId'),
            'clientSecret'            => $dsn->getOption('clientSecret'),
            'redirectUri'             => $dsn->getOption('redirectUri'),
            'urlAuthorize'            => $dsn->getOption('urlAuthorize'),
            'urlAccessToken'          => $dsn->getOption('urlAccessToken'),
            'urlResourceOwnerDetails' => $dsn->getOption('urlResourceOwnerDetails'),
            'scopes'                  => $dsn->getOption('scopes'),
        ];
    }

    protected function getSupportedSchemes(): array
    {
        return ['oauth2'];
    }
}
