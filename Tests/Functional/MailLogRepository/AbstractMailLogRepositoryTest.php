<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace Pluswerk\MailLogger\Tests\Functional\MailLogRepository;

use Override;
use ReflectionObject;
use DateTime;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Pluswerk\MailLogger\Domain\Model\MailLog;
use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use Spatie\Snapshots\MatchesSnapshots;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

abstract class AbstractMailLogRepositoryTest extends FunctionalTestCase
{
    use MatchesSnapshots;

    /**
     * @var string
     */
    private const DELAY_ANONYMIZE = '8 days';

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/mail_logger',
    ];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        // TYPO3 request needed for ConfigurationManager to work. fake it as backend request here
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    public function testInitializeObject(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $mailLogRepository = $this->initializeMailLogRepository($persistenceManager);

        $this->assertMatchesJsonSnapshot(
            json_encode(
                [
                    'lifetime' => $mailLogRepository->getLifetime(),
                    'anonymize' => $mailLogRepository->shouldAnonymize(),
                    'anonymizeAfter' => $mailLogRepository->getAnonymizeAfter(),
                    'anonymizeSymbol' => $mailLogRepository->getAnonymizeSymbol(),
                ],
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function testAdd(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $mailLogRepository = $this->initializeMailLogRepository($persistenceManager);

        $mailLog = $this->createAndSaveMailLog($mailLogRepository, $persistenceManager, 558);

        $this->assertModelSnapshot($mailLog);
    }

    public function testUpdate(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $mailLogRepository = $this->initializeMailLogRepository($persistenceManager);

        $mailLog = $this->createAndSaveMailLog($mailLogRepository, $persistenceManager, 555);

        $mailLog = $this->updatingMailLog($mailLogRepository, $persistenceManager, $mailLog);

        $this->assertModelSnapshot($mailLog);
    }

    public function testUpdateWithDelayAnonymize(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $mailLogRepository = $this->initializeMailLogRepository($persistenceManager);
        /** @var MailLog $mailLog */
        $mailLog = $this->createAndSaveMailLog($mailLogRepository, $persistenceManager, 2345);

        $mailLog->_setProperty('crdate', date_modify(new DateTime(), '-' . self::DELAY_ANONYMIZE)->getTimestamp() - 5);

        $mailLog = $this->updatingMailLog($mailLogRepository, $persistenceManager, $mailLog);

        $this->assertModelSnapshot($mailLog);
    }

    public function testCleanupDatabase(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $mailLogRepository = $this->initializeMailLogRepository($persistenceManager);

        $this->createAndSaveMailLog($mailLogRepository, $persistenceManager, 789);

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_maillogger_domain_model_maillog')
            ->update('tx_maillogger_domain_model_maillog', ['tstamp' => 0, 'crdate' => 0], ['uid' => 1]);

        $this->cleanupDatabasePart($mailLogRepository, $persistenceManager);

        /** @var MailLog $mailLog */
        $mailLog = $mailLogRepository->findAll()->getFirst();
        $this->assertModelSnapshot($mailLog);
    }

    public function testAnonymizeAll(): void
    {
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $mailLogRepository = $this->initializeMailLogRepository($persistenceManager);

        $this->createAndSaveMailLog($mailLogRepository, $persistenceManager, 7894);

        $timestamp = date_modify(new DateTime(), '-' . self::DELAY_ANONYMIZE)->getTimestamp() - 5;

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_maillogger_domain_model_maillog')
            ->update('tx_maillogger_domain_model_maillog', ['tstamp' => $timestamp, 'crdate' => $timestamp], ['uid' => 1]);

        $persistenceManager->clearState();

        $this->anonymizeAllPart($mailLogRepository, $persistenceManager);

        /** @var MailLog $mailLog */
        $mailLog = $mailLogRepository->findAll()->getFirst();
        $this->assertModelSnapshot($mailLog);
    }

    protected function initializeMailLogRepository(PersistenceManager $persistenceManager): MailLogRepository
    {
        $mailLogRepository = GeneralUtility::makeInstance(MailLogRepository::class);
        $mailLogRepository->injectPersistenceManager($persistenceManager);
        $mailLogRepository->initializeObject();
        return $mailLogRepository;
    }

    protected function getNewMailLog(int $seed): MailLog
    {
        $mailLog = new MailLog();
        $mailLog->setTypoScriptKey('typoscriptKey' . $seed);
        $mailLog->setSubject('subject' . $seed);
        $mailLog->setMailTo('mail' . $seed . '@test.test');
        $mailLog->setMailFrom('mail' . $seed . '@test.test');
        $mailLog->setMailCopy('mail' . $seed . '@test.test');
        $mailLog->setMailBlindCopy('mail' . $seed . '@test.test');
        $mailLog->setHeaders('headers' . $seed);
        $mailLog->setMessage('message' . $seed);
        return $mailLog;
    }

    /**
     * @throws NotImplementedException
     */
    protected function createAndSaveMailLog(MailLogRepository $mailLogRepository, PersistenceManager $persistenceManager, int $seed): MailLog
    {
        $mailLogRepository->add($this->getNewMailLog($seed));
        $persistenceManager->persistAll();
        $persistenceManager->clearState();

        $mailLog = $mailLogRepository->findAll()->getFirst();
        $persistenceManager->persistAll();
//        $persistenceManager->clearState();
        return $mailLog;
    }

    /**
     * @throws NotImplementedException
     */
    protected function updatingMailLog(MailLogRepository $mailLogRepository, PersistenceManager $persistenceManager, MailLog $mailLog): MailLog
    {
        $mailLogRepository->update($mailLog);
        $persistenceManager->persistAll();
        $persistenceManager->clearState();

        $mailLogResult = $mailLogRepository->findAll()->getFirst();
        $persistenceManager->persistAll();
        $persistenceManager->clearState();
        return $mailLogResult;
    }

    /**
     * @throws NotImplementedException
     */
    protected function cleanupDatabasePart(MailLogRepository $mailLogRepository, PersistenceManager $persistenceManager): void
    {
        $this->callInaccessibleMethod($mailLogRepository, 'cleanupDatabase');
        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    /**
     * @throws NotImplementedException
     */
    protected function anonymizeAllPart(MailLogRepository $mailLogRepository, PersistenceManager $persistenceManager): void
    {
        $this->callInaccessibleMethod($mailLogRepository, 'anonymizeAll');
        $persistenceManager->persistAll();
        $persistenceManager->clearState();
    }

    protected function assertModelSnapshot(?MailLog $model): void
    {
        $data = $model;
        if ($model instanceof AbstractDomainObject) {
            $data = $model->_getProperties();
            unset($data['tstamp'], $data['crdate']);
        }

        if ($data) {
            ksort($data);
        }

        $this->assertMatchesJsonSnapshot(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Helper function to call protected or private methods
     *
     * @param object $object The object to be invoked
     * @param string $name the name of the method to call
     */
    protected function callInaccessibleMethod(object $object, string $name): mixed
    {
        return (new ReflectionObject($object))->getMethod($name)->invokeArgs($object, []);
    }
}
