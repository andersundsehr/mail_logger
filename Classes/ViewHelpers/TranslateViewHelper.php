<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\ViewHelpers;

use Override;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TranslateViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('id', 'string', 'the id');
        $this->registerArgument('arguments', 'array|null', 'the arguments');
    }

    public function render(): string
    {
        $prefix = 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger.';
        return LocalizationUtility::translate($prefix . $this->arguments['id'], 'MailLogger', $this->arguments['arguments']) ?? '';
    }
}
