<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Logging;

use Override;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\FileSpool;

class LoggingDelayedTransport extends LoggingTransport implements DelayedTransportInterface
{
    #[Override]
    public function flushQueue(TransportInterface $transport): int
    {
        $originalTransport = $this->getOriginalTransport();
        if (!$originalTransport instanceof DelayedTransportInterface) {
            throw new TransportException('The wrapped mailer transport is not a delayed transport and cannot flush a queue.', 1720965302);
        }

        return $originalTransport->flushQueue($transport);
    }

    public function recover(int $timeout = 900): void
    {
        $originalTransport = $this->getOriginalTransport();
        if ($originalTransport instanceof FileSpool) {
            $originalTransport->recover($timeout);
        }
    }

    public function setMessageLimit(int $limit): void
    {
        $originalTransport = $this->getOriginalTransport();
        if ($originalTransport instanceof FileSpool) {
            $originalTransport->setMessageLimit($limit);
        }
    }

    public function setTimeLimit(int $limit): void
    {
        $originalTransport = $this->getOriginalTransport();
        if ($originalTransport instanceof FileSpool) {
            $originalTransport->setTimeLimit($limit);
        }
    }
}
