<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Logging;

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Mail\Mailer;

/**
 * Extends the core Mailer to grab and log all outgoing mails.
 */
class MailerExtender extends Mailer
{
    public function __construct(
        protected LoggingTransportFactory $loggingTransportFactory,
        ?TransportInterface $transport = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        parent::__construct($transport, $eventDispatcher);
        $this->transport = $this->loggingTransportFactory->create($this->transport);
    }

    #[Override]
    public function getRealTransport(): TransportInterface
    {
        return $this->loggingTransportFactory->create(parent::getRealTransport());
    }
}
