<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\FormDataProvider;

use Pluswerk\MailLogger\Service\MailTemplateFormatHtmlWarningService;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

final readonly class MailTemplateFormatHtmlWarningProvider implements FormDataProviderInterface
{
    private const string TABLE_NAME = 'tx_maillogger_domain_model_mailtemplate';

    public function __construct(
        private MailTemplateFormatHtmlWarningService $warningService,
    ) {
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function addData(array $result): array
    {
        if (
            $result['tableName'] === self::TABLE_NAME
            && $this->warningService->containsUnsupportedViewHelper((string)($result['databaseRow']['message'] ?? ''))
        ) {
            $this->warningService->addWarning(false);
        }

        return $result;
    }
}
