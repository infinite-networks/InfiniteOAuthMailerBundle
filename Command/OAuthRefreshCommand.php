<?php

namespace Infinite\OAuthMailerBundle\Command;

use Infinite\OAuthMailerBundle\Mailer\OAuthManager;
use Infinite\OAuthMailerBundle\Repository\OAuthMailerStorageRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OAuthRefreshCommand extends Command
{
    protected static $defaultName = 'oauth:refresh';

    public function __construct(
        private OAuthManager $manager,
        private OAuthMailerStorageRepository $repository,
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Proactively refreshes the OAuth token');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $data = $this->repository->getData();

        if (!$data) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $io->success('Nothing to refresh');
            }
            return 0;
        }

        $oldExpiryDate = $data->getAccessTokenExpires();

        if (!$this->manager->getOrRefreshAccessToken(600)) {
            $io->error('Refresh failed');
            return 1;
        }

        $newExpiryDate = $data->getAccessTokenExpires();

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            if ($oldExpiryDate->format('U') === $newExpiryDate->format('U')) {
                $io->success('Token not due for update. Token expires: '.$newExpiryDate->format('Y-m-d H:i:s'));
            } else {
                $io->success('Token refreshed successfully. New expiry date: '.$data->getAccessTokenExpires()->format('Y-m-d H:i:s'));
            }
        }

        return 0;
    }
}
