<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Logging;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Mail\Mailer;

/**
 * Extends the core Mailer to grab and log all outgoing mails.
 */
class MailerExtender extends Mailer
{
    public function __construct(?TransportInterface $transport = null, ?EventDispatcherInterface $eventDispatcher = null)
    {
        parent::__construct($transport, $eventDispatcher);
        $this->transport = new LoggingTransport($this->transport);
    }

    public function getRealTransport(): TransportInterface
    {
        return new LoggingTransport(parent::getRealTransport());
    }
}
