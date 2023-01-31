<?php declare(strict_types=1);

namespace Infinite\OAuthMailerBundle\Mailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class OAuthTransport extends SmtpTransport
{
    private ?string $token = null;

    public function __construct(
        string $host,
        private ?string $username,
        ?EventDispatcherInterface $dispatcher,
        ?LoggerInterface $logger,
        private TransportInterface $fallbackTransport,
        private OAuthManager $manager,
    )
    {
        parent::__construct(null, $dispatcher, $logger);

        /** @var SocketStream $stream */
        $stream = $this->getStream();
        $stream->disableTls();
        $stream->setHost($host);
        $stream->setPort(25);
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $this->token = $this->manager->getOrRefreshAccessToken();

        if (!$this->token || !$this->username) {
            if ($this->manager->isFallbackEnabled()) {
                return $this->fallbackTransport->send($message, $envelope);
            } else {
                throw new \Exception('OAuth2 configuration failed');
            }
        }

        return parent::send($message, $envelope);
    }

    public function __toString(): string
    {
        return 'oauth2://';
    }

    protected function doHeloCommand(): void
    {
        $capabilities = $this->callHeloCommand();

        /** @var SocketStream $stream */
        $stream = $this->getStream();
        // WARNING: !$stream->isTLS() is right, 100% sure :)
        // if you think that the ! should be removed, read the code again
        // if doing so "fixes" your issue then it probably means your SMTP server behaves incorrectly or is wrongly configured
        if (!$stream->isTLS() && \defined('OPENSSL_VERSION_NUMBER') && \array_key_exists('STARTTLS', $capabilities)) {
            $this->executeCommand("STARTTLS\r\n", [220]);

            if (!$stream->startTLS()) {
                throw new TransportException('Unable to connect with STARTTLS.');
            }

            $capabilities = $this->callHeloCommand();
        }

        if (\array_key_exists('AUTH', $capabilities)) {
            $this->executeCommand('AUTH XOAUTH2 '.base64_encode('user='.$this->username."\1auth=Bearer ".$this->token."\1\1")."\r\n", [235]);
        }
    }

    private function callHeloCommand(): array
    {
        $response = $this->executeCommand(sprintf("EHLO %s\r\n", $this->getLocalDomain()), [250]);

        $capabilities = [];
        $lines = explode("\r\n", trim($response));
        array_shift($lines);
        foreach ($lines as $line) {
            if (preg_match('/^[0-9]{3}[ -]([A-Z0-9-]+)((?:[ =].*)?)$/Di', $line, $matches)) {
                $value = strtoupper(ltrim($matches[2], ' ='));
                $capabilities[strtoupper($matches[1])] = $value ? explode(' ', $value) : [];
            }
        }

        return $capabilities;
    }
}
