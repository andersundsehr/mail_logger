<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Functional\Logging;

use Override;
use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use Pluswerk\MailLogger\Dto\MailStatus;
use Pluswerk\MailLogger\Logging\LoggingDelayedTransport;
use Pluswerk\MailLogger\Logging\LoggingTransport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\FileSpool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DelayedTransportTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/mail_logger',
    ];

    private string $spoolPath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->spoolPath = Environment::getVarPath() . '/tests/mail-spool-' . bin2hex(random_bytes(8));
    }

    #[Override]
    protected function tearDown(): void
    {
        if (is_dir($this->spoolPath)) {
            GeneralUtility::rmdir($this->spoolPath, true);
        }

        parent::tearDown();
    }

    public function testFlushingQueuedMailUpdatesExistingLogEntry(): void
    {
        $fileSpool = new FileSpool($this->spoolPath);
        $fileSpool->setMessageLimit(0);
        $fileSpool->setTimeLimit(0);

        $mailLogRepository = GeneralUtility::makeInstance(MailLogRepository::class);
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $delayedTransport = new LoggingDelayedTransport(
            $fileSpool,
            $mailLogRepository,
            $persistenceManager,
        );
        $realTransport = new LoggingTransport(
            new NullTransport(),
            $mailLogRepository,
            $persistenceManager,
        );

        $email = (new Email())
            ->from('sender@example.test')
            ->to('receiver@example.test')
            ->subject('Delayed transport test')
            ->html('<p>Body that must survive queue flushing.</p>');

        $delayedTransport->send($email);
        $persistenceManager->clearState();

        $queuedLog = $this->getSingleMailLog();
        self::assertSame('Email queued', $queuedLog['result']);
        self::assertSame(MailStatus::QUEUED->value, (int)$queuedLog['status']);
        self::assertStringContainsString('Body that must survive queue flushing.', $queuedLog['message']);
        self::assertStringContainsString('X-Mail-Logger-Correlation-Id:', $queuedLog['headers']);

        self::assertSame(MailStatus::SENT_OK->value, $delayedTransport->flushQueue($realTransport));
        $persistenceManager->clearState();

        $sentLog = $this->getSingleMailLog();
        self::assertSame((int)$queuedLog['uid'], (int)$sentLog['uid']);
        self::assertSame('Email Nulled (NullTransport)', $sentLog['result']);
        self::assertSame(MailStatus::NOT_SENT->value, (int)$sentLog['status']);
        self::assertSame($queuedLog['subject'], $sentLog['subject']);
        self::assertSame($queuedLog['message'], $sentLog['message']);
        self::assertSame($queuedLog['mail_from'], $sentLog['mail_from']);
        self::assertSame($queuedLog['mail_to'], $sentLog['mail_to']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getSingleMailLog(): array
    {
        $mailLogs = $this->getConnectionPool()
            ->getConnectionForTable('tx_maillogger_domain_model_maillog')
            ->select(
                ['*'],
                'tx_maillogger_domain_model_maillog',
                [],
                [],
                ['uid' => 'ASC'],
            )
            ->fetchAllAssociative();

        self::assertCount(1, $mailLogs);

        return $mailLogs[0];
    }
}
