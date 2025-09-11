<?php

namespace Pluswerk\MailLogger\Tests\Functional\MailLogRepository;

use Override;

final class Anonymize7daysLifeTime30daysTest extends AbstractMailLogRepositoryTest
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendRootPage(1, [
            'EXT:mail_logger/Configuration/TypoScript/setup.typoscript',
            'EXT:mail_logger/Tests/Fixtures/MailLogRepositoryTest/Anonymize7daysLifeTime30days.typoscript',
        ]);
    }
}
