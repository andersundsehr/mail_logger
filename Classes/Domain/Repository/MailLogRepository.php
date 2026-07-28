<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Domain\Repository;

use Override;
use DateTime;
use InvalidArgumentException;
use Pluswerk\MailLogger\Domain\Model\MailLog;
use Pluswerk\MailLogger\Dto\MailLogFilter;
use Pluswerk\MailLogger\Service\CleanupSettingsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<MailLog>
 */
class MailLogRepository extends Repository
{
    private const string CORRELATION_HEADER = 'X-Mail-Logger-Correlation-Id';

    protected $defaultOrderings = [
        'crdate' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Constructs a new Repository
     */
    public function __construct(
        private readonly CleanupSettingsService $cleanupSettingsService,
    ) {
        parent::__construct();
    }

    public function initializeObject(): void
    {
        /** @var Typo3QuerySettings $querySettings */

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param MailLog $mailLog
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     * @throws IllegalObjectTypeException
     */
    #[Override]
    public function add($mailLog): void
    {
        if (!$mailLog->getCrdate()) {
            $mailLog->_setProperty('crdate', time());
        }

        if (!$mailLog->getTstamp()) {
            $mailLog->_setProperty('tstamp', time());
        }

        $this->anonymizeMailLogIfNeeded($mailLog);
        parent::add($mailLog);
    }

    /**
     * @param MailLog $mailLog
     * @throws UnknownObjectException
     * @throws IllegalObjectTypeException
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    #[Override]
    public function update($mailLog): void
    {
        if ($mailLog->getTstamp() === null) {
            $mailLog->_setProperty('tstamp', time());
        }

        $this->anonymizeMailLogIfNeeded($mailLog);
        parent::update($mailLog);
    }

    private function anonymizeMailLogIfNeeded(MailLog $mailLog): void
    {
        if (!$this->cleanupSettingsService->isLoaded()) {
            return;
        }

        if ($mailLog->getCrdate() === null) {
            throw new InvalidArgumentException('MailLog must have a crdate', 8348363881);
        }

        if (!$this->cleanupSettingsService->shouldAnonymize()) {
            return;
        }

        $anonymizeAfter = $this->cleanupSettingsService->getAnonymizeAfter();
        $anonymizeDate = new DateTime();
        if ($anonymizeDate->modify('-' . $anonymizeAfter) === false) {
            throw new InvalidArgumentException('Invalid anonymization period', 8348363882);
        }

        if ($mailLog->getCrdate() > $anonymizeDate->getTimestamp()) {
            return;
        }

        $anonymizeSymbol = $this->cleanupSettingsService->getAnonymizeSymbol();
        $mailLog->setSubject($anonymizeSymbol);
        $mailLog->setMessage($anonymizeSymbol);
        $mailLog->setMailFrom($anonymizeSymbol);
        $mailLog->setMailTo($anonymizeSymbol);
        $mailLog->setMailCopy($anonymizeSymbol);
        $mailLog->setMailBlindCopy($anonymizeSymbol);
        $mailLog->setHeaders($anonymizeSymbol);
    }

    public function getLifetime(): string
    {
        return $this->cleanupSettingsService->getLifetime();
    }

    public function shouldAnonymize(): bool
    {
        return $this->cleanupSettingsService->shouldAnonymize();
    }

    public function getAnonymizeSymbol(): string
    {
        return $this->cleanupSettingsService->getAnonymizeSymbol();
    }

    public function getAnonymizeAfter(): string
    {
        return $this->cleanupSettingsService->getAnonymizeAfter();
    }

    public function findByCorrelationId(string $correlationId): ?MailLog
    {
        $query = $this->createQuery();
        $query->matching(
            $query->like(
                'headers',
                '%' . self::CORRELATION_HEADER . ': ' . $correlationId . '%',
            ),
        );

        $mailLog = $query->execute()->getFirst();
        return $mailLog instanceof MailLog ? $mailLog : null;
    }

    /**
     * @return QueryResultInterface<int, MailLog>
     */
    public function findByFilter(MailLogFilter $filter): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];
        $textFilters = [
            'mailTo' => $filter->getMailTo(),
            'mailFrom' => $filter->getMailFrom(),
            'subject' => $filter->getSubject(),
        ];

        foreach ($textFilters as $propertyName => $value) {
            if ($value !== '') {
                $constraints[] = $query->like($propertyName, '%' . $value . '%');
            }
        }

        if ($filter->getStatus() !== null) {
            $constraints[] = $query->equals('status', $filter->getStatus()->value);
        }

        if ($constraints !== []) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query->execute();
    }
}
