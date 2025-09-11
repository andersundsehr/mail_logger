<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\ViewHelpers;

use Override;
use Pluswerk\MailLogger\Domain\Model\MailLog;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class IsPlainTextMailLogViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('mailLog', MailLog::class, 'MailLog to analyze');
    }

    public function render(): bool
    {
        $isPlainText = false;
        /** @var MailLog $mailLog */
        $mailLog = $this->arguments['mailLog'];
        foreach (explode("\n", $mailLog->getHeaders()) as $headerLine) {
            if (stripos($headerLine, 'Content-Type:') === 0 && stripos($headerLine, 'text/plain') !== false) {
                $isPlainText = true;
                break;
            }
        }

        return $isPlainText;
    }
}
