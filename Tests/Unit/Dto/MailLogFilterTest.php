<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Unit\Dto;

use PHPUnit\Framework\TestCase;
use Pluswerk\MailLogger\Dto\MailLogFilter;
use Pluswerk\MailLogger\Dto\MailStatus;

final class MailLogFilterTest extends TestCase
{
    public function testToArrayReturnsNormalizedActionArguments(): void
    {
        $filter = new MailLogFilter(
            mailTo: ' recipient@example.test ',
            mailFrom: ' sender@example.test ',
            subject: ' Subject ',
            status: MailStatus::NOT_SENT,
        );

        self::assertSame([
            'mailTo' => 'recipient@example.test',
            'mailFrom' => 'sender@example.test',
            'subject' => 'Subject',
            'status' => '2',
        ], $filter->toArray());
    }
}
