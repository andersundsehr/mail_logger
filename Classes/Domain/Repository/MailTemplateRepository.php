<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Domain\Repository;

use Pluswerk\MailLogger\Domain\Model\MailTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @method MailTemplate findOneByTypoScriptKey(string $key)
 * @extends Repository<MailTemplate>
 */
class MailTemplateRepository extends Repository
{
    public function initializeObject(): void
    {
        /** @var Typo3QuerySettings $querySettings */
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function findOneByTypoScriptKeyAndLanguage(string $typoScriptKey, ?int $languageUid = null): ?MailTemplate
    {
        $mailTemplate = null;
        if ($languageUid === null) {
            $mailTemplate = $this->findOneByTypoScriptKey($typoScriptKey);
        } else {
            // Destroy session because the objects are stored there without language
            /** @var Session $session */
            $session = GeneralUtility::makeInstance(Session::class);
            $session->destroy();

            $query = $this->createQuery();
            $querySettings = $query->getQuerySettings();
            assert($querySettings instanceof Typo3QuerySettings);
            $querySettings->setLanguageUid($languageUid);
            $query->matching($query->equals('typoScriptKey', $typoScriptKey));
            $query->setLimit(1);
            $mailTemplate = $query->execute(false)->getFirst();

            $session->destroy();
        }

        return $mailTemplate;
    }
}
