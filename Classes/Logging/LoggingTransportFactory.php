<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Logging;

use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Factory for creating LoggingTransport instances with proper dependency injection.
 */
class LoggingTransportFactory
{
    public function __construct(
        protected MailLogRepository $mailLogRepository,
        protected PersistenceManager $persistenceManager,
    ) {
    }

    public function create(TransportInterface $originalTransport): LoggingTransport
    {
        if ($originalTransport instanceof DelayedTransportInterface) {
            return new LoggingDelayedTransport(
                $originalTransport,
                $this->mailLogRepository,
                $this->persistenceManager,
            );
        }

        return new LoggingTransport(
            $originalTransport,
            $this->mailLogRepository,
            $this->persistenceManager,
        );
    }
}
