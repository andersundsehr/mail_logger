<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pluswerk\MailLogger\Domain\Model\MailTemplate;
use Pluswerk\MailLogger\Service\MailTemplateContentTransformer;

final class MailTemplateContentTransformerTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function templateContentDataProvider(): array
    {
        return [
            'tag notation' => [
                '<f:format.html>{body}</f:format.html>',
                '<f:transform.html><f:sanitize.html>{body}</f:sanitize.html></f:transform.html>',
            ],
            'tag notation with arguments' => [
                '<f:format.html parseFuncTSPath="lib.parseFunc_RTE">{body}</f:format.html>',
                '<f:transform.html><f:sanitize.html>{body}</f:sanitize.html></f:transform.html>',
            ],
            'inline pipe notation' => [
                '{body -> f:format.html()}',
                '{body -> f:sanitize.html() -> f:transform.html()}',
            ],
            'inline pipe notation with arguments' => [
                '{body -> f:format.html(parseFuncTSPath: \'lib.parseFunc_RTE\')}',
                '{body -> f:sanitize.html() -> f:transform.html()}',
            ],
            'combined notation' => [
                '<f:format.html>{headline -> f:format.html()}</f:format.html>',
                '<f:transform.html><f:sanitize.html>{headline -> f:sanitize.html() -> f:transform.html()}</f:sanitize.html></f:transform.html>',
            ],
            'tag notation with arguments fuzzy' => [
                '<f:format.html  parseFuncTSPath = "lib.parseFunc_RTE">{body}
                </f:format.html>',
                '<f:transform.html><f:sanitize.html>{body}
                </f:sanitize.html></f:transform.html>',
            ],
            'inline pipe notation fuzzy' => [
                '{body -> f:format.html ()}',
                '{body -> f:sanitize.html() -> f:transform.html()}',
            ],
        ];
    }

    #[DataProvider('templateContentDataProvider')]
    public function testTransformReplacesFormatHtml(string $content, string $expected): void
    {
        self::assertSame($expected, (new MailTemplateContentTransformer())->transform($content));
    }

    public function testTransformMailTemplateReturnsTransformedClone(): void
    {
        $mailTemplate = (new MailTemplate())->setMessage('{body -> f:format.html()}');

        $transformedMailTemplate = (new MailTemplateContentTransformer())->transformMailTemplate($mailTemplate);

        self::assertNotSame($mailTemplate, $transformedMailTemplate);
        self::assertSame('{body -> f:format.html()}', $mailTemplate->getMessage());
        self::assertSame('{body -> f:sanitize.html() -> f:transform.html()}', $transformedMailTemplate->getMessage());
    }
}
