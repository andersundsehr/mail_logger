<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Service;

use Pluswerk\MailLogger\Domain\Model\MailTemplate;

final class MailTemplateContentTransformer
{
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
            static fn(array $matches): string => '<f:transform.html><f:sanitize.html>' . $matches[1] . '</f:sanitize.html></f:transform.html>',
            $content
        );
    }

    private function transformInlineNotation(string $content): string
    {
        return (string)preg_replace(
            '#->\s*f:format\.html\s*\([^)]*\)#',
            '-> f:sanitize.html() -> f:transform.html()',
            $content
        );
    }
}
