<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Functional\MailLogRepository;

use Override;

final class AnonymizeDirectlyLifeTimeEmptyTest extends AbstractMailLogRepositoryTest
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendRootPage(1, [
            'EXT:mail_logger/Configuration/TypoScript/setup.typoscript',
            'EXT:mail_logger/Tests/Fixtures/MailLogRepositoryTest/AnonymizeDirectlyLifeTimeEmpty.typoscript',
        ]);
    }
}
