<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Logging;

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extends the core Mailer to grab and log all outgoing mails.
 */
class MailerExtender extends Mailer
{
    protected LoggingTransportFactory $loggingTransportFactory;

    public function __construct(
        ?TransportInterface $transport = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($transport, $eventDispatcher);

        $this->loggingTransportFactory = GeneralUtility::makeInstance(LoggingTransportFactory::class);
        $this->transport = $this->loggingTransportFactory->create($this->transport);
    }

    #[Override]
    public function getTransport(): TransportInterface
    {
        if (
            $this->transport instanceof LoggingTransport
            && $this->transport->getOriginalTransport() instanceof DelayedTransportInterface
        ) {
            return $this->transport->getOriginalTransport();
        }

        return parent::getTransport();
    }

    #[Override]
    public function getRealTransport(): TransportInterface
    {
        return $this->loggingTransportFactory->create(parent::getRealTransport());
    }
}
