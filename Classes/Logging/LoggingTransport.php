<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Logging;

use Stringable;
use Throwable;
use Pluswerk\MailLogger\Domain\Model\MailLog;
use Pluswerk\MailLogger\Domain\Model\TemplateBasedMailMessage;
use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use Pluswerk\MailLogger\Dto\MailStatus;
use Pluswerk\MailLogger\Dto\SendResult;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\AbstractMultipartPart;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class LoggingTransport implements TransportInterface, Stringable
{
    public function __construct(protected TransportInterface $originalTransport)
    {
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $this->fixTcaIfNotPresentIsUsedInInstallTool();

        $mailLogRepository = GeneralUtility::makeInstance(MailLogRepository::class);

        // write mail to log before send
        $mailLog = GeneralUtility::makeInstance(MailLog::class);
        $this->assignMailLog($mailLog, $message);
        $mailLogRepository->add($mailLog);
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();


        $sendResult = $this->originalSend($message, $envelope);

        // write result to log after send
        $this->assignMailLog($mailLog, $message);
        $mailLog->setResult($sendResult->result);
        $mailLog->setStatus($sendResult->status->value);
        $mailLog->setDebug($sendResult->getDebugMessage());

        $mailLogRepository->update($mailLog);
        GeneralUtility::makeInstance(PersistenceManager::class)->persistAll();

        if ($sendResult->throwable) {
            throw $sendResult->throwable;
        }

        return $sendResult->sentMessage;
    }

    public function getOriginalTransport(): TransportInterface
    {
        return $this->originalTransport;
    }

    private function originalSend(RawMessage $message, Envelope $envelope = null): SendResult
    {
        try {
            $sendMessage = $this->originalTransport->send($message, $envelope);
            if (!$sendMessage) {
                return new SendResult('Email not sent', MailStatus::NOT_SENT);
            }

            $result = 'Email sent';
            $status = MailStatus::SENT_OK;
            if (
                $this->originalTransport instanceof DelayedTransportInterface
                || $this->originalTransport instanceof SendmailTransport
            ) {
                $result = 'Email queued';
                $status = MailStatus::QUEUED;
            }

            if ($this->originalTransport instanceof NullTransport) {
                $result = 'Email Nulled (NullTransport)';
                $status = MailStatus::NOT_SENT;
            }

            return new SendResult($result, $status, $sendMessage->getDebug(), $sendMessage);
        } catch (Throwable $throwable) {
            return new SendResult('Email not sent. Error: ' . $throwable->getMessage(), MailStatus::NOT_SENT, throwable: $throwable);
        }
    }

    public function __toString(): string
    {
        return $this->originalTransport->__toString();
    }

    protected function assignMailLog(MailLog $mailLog, RawMessage $message): void
    {
        if (!$message instanceof Email) {
            return;
        }

        $messageBody = $message->getBody();
        $mailLog->setMessage($this->getBodyAsHtml($messageBody));
        $mailLog->setSubject($message->getSubject());
        $mailLog->setMailFrom($this->addressesToString($message->getFrom()));
        $mailLog->setMailTo($this->addressesToString($message->getTo()));
        $mailLog->setMailCopy($this->addressesToString($message->getCc()));
        $mailLog->setMailBlindCopy($this->addressesToString($message->getBcc()));
        $mailLog->setHeaders($message->getHeaders()->toString());
        if ($message instanceof TemplateBasedMailMessage) {
            $mailLog->setTypoScriptKey($message->getTypoScriptKey());
        }
    }

    protected function getBodyAsHtml(AbstractPart $part): string
    {
        if ($part instanceof AbstractMultipartPart) {
            $messageString = '';
            foreach ($part->getParts() as $childPart) {
                $messageString .= $this->getBodyAsHtml($childPart);
                $messageString .= '----------------------------------------<br>';
            }

            return $messageString;
        }

        $body = $part instanceof TextPart && $part->getMediaType() === 'text' ? $part->getBody() : $part->asDebugString(
        );
        $body = str_replace(["\t", "\r"], '', $body);
        if ($part->getMediaSubtype() === 'plain') {
            $body = str_replace(PHP_EOL, '<br>', $body);
        } else {
            $body = str_replace(PHP_EOL, '', $body);
        }

        return $body . '<br>';
    }

    /**
     * @param Address[] $addresses
     */
    protected function addressesToString(array $addresses): string
    {
        return implode(
            ', ',
            array_map(
                static fn(Address $address): string => $address->getName() . ' <' . $address->getAddress() . '>',
                $addresses,
            ),
        );
    }

    protected function fixTcaIfNotPresentIsUsedInInstallTool(): void
    {
        if (empty($GLOBALS['TCA']['tx_maillogger_domain_model_maillog'])) {
            $GLOBALS['TCA']['tx_maillogger_domain_model_maillog'] = require __DIR__ . '/../../Configuration/TCA/tx_maillogger_domain_model_maillog.php';
        }
    }
}
