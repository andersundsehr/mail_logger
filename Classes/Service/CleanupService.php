<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Service;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;

class CleanupService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string CACHE_KEY = 'tx_maillogger_cleanup_lock';

    private const string TABLE_NAME = 'tx_maillogger_domain_model_maillog';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly CleanupSettingsService $cleanupSettingsService,
        private readonly FrontendInterface $cache,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
    }

    /**
     * Try to run cleanup if not already running and interval has passed.
     * Uses cache as lock mechanism to prevent parallel runs.
     */
    public function tryRunCleanup(): void
    {
        // Check if lock exists (cleanup recently ran or is running)
        if ($this->cache->has(self::CACHE_KEY)) {
            return;
        }

        $minInterval = $this->getCleanupMinInterval();

        // Acquire lock by setting cache entry with lifetime = minInterval
        $this->cache->set(self::CACHE_KEY, time(), [], $minInterval);

        try {
            $this->cleanupDatabase();
            $this->anonymizeAll();
        } catch (Exception $exception) {
            $this->logger?->error('Mail logger cleanup failed', [
                'exception' => $exception,
                'message' => $exception->getMessage(),
            ]);

            // Release lock on failure so cleanup can be retried
            $this->cache->remove(self::CACHE_KEY);
        }
    }

    /**
     * Delete old mail log entries
     */
    private function cleanupDatabase(): void
    {
        $lifetime = $this->cleanupSettingsService->getLifetime();

        if ($lifetime === '') {
            return;
        }

        $deletionTimestamp = strtotime('-' . $lifetime);
        if ($deletionTimestamp === false) {
            throw new Exception(
                sprintf('Given lifetime string in TypoScript is wrong. lifetime: "%s"', $lifetime),
                9235306650
            );
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->delete(self::TABLE_NAME)
            ->where($queryBuilder->expr()->lte('crdate', $queryBuilder->createNamedParameter($deletionTimestamp)))
            ->executeStatement();
    }

    /**
     * Anonymize mail logs older than specified time
     */
    private function anonymizeAll(): void
    {
        if (!$this->cleanupSettingsService->shouldAnonymize()) {
            return;
        }

        $anonymizeAfter = $this->cleanupSettingsService->getAnonymizeAfter();
        $anonymizeSymbol = $this->cleanupSettingsService->getAnonymizeSymbol();

        $timestamp = strtotime('-' . $anonymizeAfter);
        if ($timestamp === false) {
            throw new Exception(
                sprintf('Given lifetime string in TypoScript is wrong. anonymizeAfter: "%s"', $anonymizeAfter),
                3198610142
            );
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $namedParameterAnonymizeSymbol = $queryBuilder->createNamedParameter($anonymizeSymbol);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update(self::TABLE_NAME)
            ->set('tstamp', time())
            ->set('subject', $anonymizeSymbol)
            ->set('message', $anonymizeSymbol)
            ->set('mail_from', $anonymizeSymbol)
            ->set('mail_to', $anonymizeSymbol)
            ->set('mail_copy', $anonymizeSymbol)
            ->set('mail_blind_copy', $anonymizeSymbol)
            ->set('headers', $anonymizeSymbol)
            ->set('debug', $anonymizeSymbol)
            ->where(
                $queryBuilder->expr()->lte('crdate', $queryBuilder->createNamedParameter($timestamp)),
                // Skip already fully anonymized records
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('subject', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('message', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('mail_from', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('mail_to', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('mail_copy', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('mail_blind_copy', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('headers', $namedParameterAnonymizeSymbol),
                    $queryBuilder->expr()->neq('debug', $namedParameterAnonymizeSymbol),
                ),
            )
            ->executeStatement();
    }

    private function getCleanupMinInterval(): int
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $config = $this->extensionConfiguration->get('mail_logger');
        return (int)($config['cleanupMinInterval'] ?? 3600);
    }
}
