<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Functional\TemplateBasedMailMessage;

use Override;
use Pluswerk\MailLogger\Dto\MailStatus;
use Pluswerk\MailLogger\Utility\MailUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TemplateBasedMailMessageTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/mail_logger',
    ];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/TemplateBasedMailMessageTest/mail_templates.csv');
        $this->setUpFrontendRootPage(1, [
            'EXT:mail_logger/Configuration/TypoScript/setup.typoscript',
            'EXT:mail_logger/Tests/Fixtures/TemplateBasedMailMessageTest/setup.typoscript',
        ]);

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = '';
    }

    public function testConfiguredTemplateIsRenderedAndLoggedWhenSentThroughNullTransport(): void
    {
        $mail = MailUtility::getMailByKey('functionalMail', null, [
            'name' => 'Ada',
            'color' => 'blue',
        ]);

        self::assertTrue($mail->send());

        $mailLog = $this->getSingleMailLog();
        self::assertSame('functionalMail', $mailLog['typo_script_key']);
        self::assertSame('Functional subject for Ada', $mailLog['subject']);
        self::assertStringContainsString('<p>Rendered body for Ada with blue</p>', $mailLog['message']);
        self::assertSame('Sender Ada <sender@example.test>', $mailLog['mail_from']);
        self::assertSame('Receiver Ada <receiver@example.test>', $mailLog['mail_to']);
        self::assertSame('Email Nulled (NullTransport)', $mailLog['result']);
        self::assertSame(MailStatus::NOT_SENT->value, (int)$mailLog['status']);
        self::assertStringContainsString('Subject: Functional subject for Ada', $mailLog['headers']);
    }

    public function testManuallyAssignedSubjectIsKeptWhenTemplateDoesNotConfigureSubject(): void
    {
        $mail = MailUtility::getMailByKey('directSubjectMail', null, [
            'name' => 'Ada',
        ]);
        $mail->setSubject('Manual subject for Ada');

        self::assertTrue($mail->send());

        $mailLog = $this->getSingleMailLog();
        self::assertSame('directSubjectMail', $mailLog['typo_script_key']);
        self::assertSame('Manual subject for Ada', $mailLog['subject']);
        self::assertStringContainsString('<p>Body with manually assigned subject for Ada</p>', $mailLog['message']);
        self::assertSame('Email Nulled (NullTransport)', $mailLog['result']);
        self::assertSame(MailStatus::NOT_SENT->value, (int)$mailLog['status']);
    }

    public function testEachSentMailCreatesItsOwnLogEntry(): void
    {
        foreach (['Ada', 'Grace'] as $name) {
            $mail = MailUtility::getMailByKey('functionalMail', null, [
                'name' => $name,
                'color' => 'blue',
            ]);

            self::assertTrue($mail->send());
        }

        $connection = $this->getConnectionPool()->getConnectionForTable('tx_maillogger_domain_model_maillog');
        $mailLogs = $connection->select(
            ['subject'],
            'tx_maillogger_domain_model_maillog',
            [],
            [],
            ['uid' => 'ASC'],
        )->fetchFirstColumn();

        self::assertSame([
            'Functional subject for Ada',
            'Functional subject for Grace',
        ], $mailLogs);
    }

    /**
     * @return array<string, mixed>
     */
    private function getSingleMailLog(): array
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_maillogger_domain_model_maillog');
        $mailLogs = $connection->select(
            ['*'],
            'tx_maillogger_domain_model_maillog',
            [],
            [],
            ['uid' => 'ASC'],
        )->fetchAllAssociative();

        self::assertCount(1, $mailLogs);

        return $mailLogs[0];
    }
}
