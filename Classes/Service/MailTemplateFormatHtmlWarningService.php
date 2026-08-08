<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Service;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

final readonly class MailTemplateFormatHtmlWarningService
{
    private const string MESSAGE_LABEL = 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_mailtemplate.format_html_warning';

    private const string TITLE_LABEL = 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_mailtemplate.format_html_warning.title';

    public function __construct(
        private MailTemplateContentTransformer $mailTemplateContentTransformer,
        private FlashMessageService $flashMessageService,
    ) {
    }

    public function containsUnsupportedViewHelper(string $message): bool
    {
        return $this->mailTemplateContentTransformer->transform($message) !== $message;
    }

    public function addWarning(bool $storeInSession): void
    {
        $languageService = $GLOBALS['LANG'];
        $message = $languageService->sL(self::MESSAGE_LABEL);
        $queue = $this->flashMessageService->getMessageQueueByIdentifier();

        foreach ($queue->getAllMessages(ContextualFeedbackSeverity::WARNING) as $flashMessage) {
            if ($flashMessage->getMessage() === $message) {
                return;
            }
        }

        $queue->enqueue(new FlashMessage(
            $message,
            $languageService->sL(self::TITLE_LABEL),
            ContextualFeedbackSeverity::WARNING,
            $storeInSession,
        ));
    }
}
