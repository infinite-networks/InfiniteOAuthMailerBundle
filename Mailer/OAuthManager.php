<?php declare(strict_types=1);

namespace Infinite\OAuthMailerBundle\Mailer;

use Infinite\OAuthMailerBundle\Repository\OAuthMailerStorageRepository;
use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Component\Mailer\Transport\Dsn;

class OAuthManager
{
    private bool $enableFallback = true;

    public function __construct(
        private OAuthTransportFactory $transportFactory,
        private string $mailerDsn,
        private OAuthMailerStorageRepository $repository,
    )
    {
    }

    public function canBeConfigured(): bool
    {
        return $this->transportFactory->supports(Dsn::fromString($this->mailerDsn));
    }

    public function getSettings(): array
    {
        return OAuthTransportFactory::getOauth2Settings(Dsn::fromString($this->mailerDsn));
    }

    public function createProvider(): GenericProvider
    {
        return new GenericProvider(self::getSettings());
    }

    public function getOrRefreshAccessToken(int $secondsThreshold = 60): ?string
    {
        $data = $this->repository->getData();

        if (!$data) {
            // Access token unavailable. OAuthTransport will fall back to its fallback transport.
            return null;
        }

        $threshold = new \DateTime("+ $secondsThreshold seconds");

        if ($data->getAccessTokenExpires() < $threshold) {
            $provider = $this->createProvider();
            try {
                $newToken = $provider->getAccessToken('refresh_token', [
                    'refresh_token' => $data->getRefreshToken(),
                ]);
                $data = $this->repository->createOrUpdateDataFrom($newToken);
                $this->repository->save($data);
            } catch (\Exception $ex) {
                // No choice but to use the fallback method now
                return null;
            }
        }

        return $data->getAccessToken();
    }

    public function disableFallback(): void
    {
        $this->enableFallback = false;
    }

    public function isFallbackEnabled(): bool
    {
        return $this->enableFallback;
    }
}
