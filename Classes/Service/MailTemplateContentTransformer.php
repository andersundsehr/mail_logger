<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Service;

use Pluswerk\MailLogger\Domain\Model\MailTemplate;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final readonly class MailTemplateContentTransformer
{
    public function __construct(
        private ?ExtensionConfiguration $extensionConfiguration = null,
    ) {
    }

    public function transformMailTemplate(MailTemplate $mailTemplate): MailTemplate
    {
        $transformedMailTemplate = clone $mailTemplate;
        $transformedMailTemplate->setMessage($this->transform($mailTemplate->getMessage()));

        return $transformedMailTemplate;
    }

    public function transform(string $content): string
    {
        $content = $this->transformTagNotation($content);

        return $this->transformInlineNotation($content);
    }

    private function transformTagNotation(string $content): string
    {
        return (string)preg_replace_callback(
            '#<f:format\.html\b[^>]*>(.*?)</f:format\.html>#s',
            fn(array $matches): string => $this->wrapTagNotationContent($matches[1]),
            $content
        );
    }

    private function transformInlineNotation(string $content): string
    {
        return (string)preg_replace(
            '#->\s*f:format\.html\s*\([^)]*\)#',
            $this->shouldSanitizeHtml()
                ? '-> f:sanitize.html() -> f:transform.html()'
                : '-> f:transform.html()',
            $content
        );
    }

    private function wrapTagNotationContent(string $content): string
    {
        if ($this->shouldSanitizeHtml()) {
            return '<f:transform.html><f:sanitize.html>' . $content . '</f:sanitize.html></f:transform.html>';
        }

        return '<f:transform.html>' . $content . '</f:transform.html>';
    }

    private function shouldSanitizeHtml(): bool
    {
        try {
            return (bool)($this->extensionConfiguration?->get('mail_logger', 'transformFormatHtmlWithSanitize') ?? false);
        } catch (ExtensionConfigurationPathDoesNotExistException) {
            return false;
        }
    }
}
