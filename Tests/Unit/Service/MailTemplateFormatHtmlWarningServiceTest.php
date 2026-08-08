<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pluswerk\MailLogger\Service\MailTemplateContentTransformer;
use Pluswerk\MailLogger\Service\MailTemplateFormatHtmlWarningService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;

final class MailTemplateFormatHtmlWarningServiceTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function messageDataProvider(): array
    {
        return [
            'tag notation' => ['<f:format.html>{body}</f:format.html>', true],
            'inline notation' => ['{body -> f:format.html()}', true],
            'supported view helper' => ['{body -> f:transform.html()}', false],
            'plain HTML' => ['<p>{body}</p>', false],
        ];
    }

    #[DataProvider('messageDataProvider')]
    public function testDetectsViewHelpersChangedByTheTransformer(string $message, bool $expected): void
    {
        $warningService = new MailTemplateFormatHtmlWarningService(
            new MailTemplateContentTransformer(),
            new FlashMessageService(),
        );

        self::assertSame($expected, $warningService->containsUnsupportedViewHelper($message));
    }
}
