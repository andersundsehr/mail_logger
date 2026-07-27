<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Hook;

use Pluswerk\MailLogger\Service\MailTemplateFormatHtmlWarningService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;

final readonly class MailTemplateDataHandlerHook
{
    private const string TABLE_NAME = 'tx_maillogger_domain_model_mailtemplate';

    public function __construct(
        private MailTemplateFormatHtmlWarningService $warningService,
    ) {
    }

    /**
     * @param array<string, mixed> $fieldArray
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string|int $id,
        array $fieldArray,
        DataHandler $dataHandler,
    ): void {
        if ($table !== self::TABLE_NAME) {
            return;
        }

        $recordUid = $status === 'new' ? ($dataHandler->substNEWwithIDs[$id] ?? null) : (int)$id;
        if (!is_int($recordUid) || $recordUid <= 0) {
            return;
        }

        $record = BackendUtility::getRecord(self::TABLE_NAME, $recordUid, 'message');
        if ($record !== null && $this->warningService->containsUnsupportedViewHelper((string)$record['message'])) {
            $this->warningService->addWarning(true);
        }
    }
}
