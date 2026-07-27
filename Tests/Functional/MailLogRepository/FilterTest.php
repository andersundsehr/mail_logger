<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Functional\MailLogRepository;

use PHPUnit\Framework\Attributes\DataProvider;
use Pluswerk\MailLogger\Domain\Model\MailLog;
use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use Pluswerk\MailLogger\Dto\MailLogFilter;
use Pluswerk\MailLogger\Dto\MailStatus;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/mail_logger',
    ];

    /**
     * @return iterable<string, array{MailLogFilter, string}>
     */
    public static function filterProvider(): iterable
    {
        yield 'recipient' => [
            new MailLogFilter(mailTo: ' grace@example.test '),
            'Payment failed',
        ];
        yield 'sender' => [
            new MailLogFilter(mailFrom: 'support@example.test'),
            'Deployment queued',
        ];
        yield 'subject' => [
            new MailLogFilter(subject: 'Monthly invoice'),
            'Monthly invoice',
        ];
        yield 'status' => [
            new MailLogFilter(status: MailStatus::NOT_SENT),
            'Payment failed',
        ];
        yield 'combined filters' => [
            new MailLogFilter(
                mailFrom: 'billing@example.test',
                status: MailStatus::NOT_SENT,
            ),
            'Payment failed',
        ];
    }

    #[DataProvider('filterProvider')]
    public function testFindByFilterReturnsMatchingMailLogs(MailLogFilter $filter, string $expectedSubject): void
    {
        $this->persistMailLogs();

        $mailLogs = GeneralUtility::makeInstance(MailLogRepository::class)
            ->findByFilter($filter)
            ->toArray();

        self::assertCount(1, $mailLogs);
        self::assertSame($expectedSubject, $mailLogs[0]->getSubject());
    }

    public function testEmptyFilterReturnsAllMailLogs(): void
    {
        $this->persistMailLogs();

        $mailLogs = GeneralUtility::makeInstance(MailLogRepository::class)
            ->findByFilter(new MailLogFilter());

        self::assertCount(3, $mailLogs);
    }

    private function persistMailLogs(): void
    {
        $repository = GeneralUtility::makeInstance(MailLogRepository::class);
        $repository->add($this->createMailLog(
            'Monthly invoice',
            'Shop <shop@example.test>',
            'Ada <ada@example.test>',
            MailStatus::SENT_OK,
            'Email sent',
        ));
        $repository->add($this->createMailLog(
            'Payment failed',
            'Billing <billing@example.test>',
            'Grace <grace@example.test>',
            MailStatus::NOT_SENT,
            'SMTP connection refused',
        ));
        $repository->add($this->createMailLog(
            'Deployment queued',
            'Support <support@example.test>',
            'Operations <ops@example.test>',
            MailStatus::QUEUED,
            'Email queued',
        ));

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    private function createMailLog(
        string $subject,
        string $mailFrom,
        string $mailTo,
        MailStatus $status,
        string $result,
    ): MailLog {
        $mailLog = new MailLog();
        $mailLog->setSubject($subject);
        $mailLog->setMailFrom($mailFrom);
        $mailLog->setMailTo($mailTo);
        $mailLog->setStatus($status->value);
        $mailLog->setResult($result);

        return $mailLog;
    }
}
