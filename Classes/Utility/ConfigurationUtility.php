<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Utility;

use Exception;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;

class ConfigurationUtility
{
    /** @var array<array-key, mixed> */
    protected static array $currentModuleConfiguration = [];

    /**
     * @return array<array-key, mixed>
     */
    public static function getCurrentModuleConfiguration(string $key): array
    {
        if (!self::$currentModuleConfiguration) {
            // we always use the BackendConfigurationManager, because flux is overwriting the ConfigurationManager and always uses the FrontendConfigurationManager instead of the correct one for the current context
            $request = $GLOBALS['TYPO3_REQUEST'] ?? throw new Exception('No $GLOBALS[\'TYPO3_REQUEST\'] found', 1688138054);
            $fullTypoScript = GeneralUtility::makeInstance(BackendConfigurationManager::class)->getTypoScriptSetup($request);

            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

            if (empty($fullTypoScript['module.']['tx_maillogger.'])) {
                throw new Exception('Constants and setup TypoScript are not included!', 7780827935);
            }

            self::$currentModuleConfiguration = $typoScriptService->convertTypoScriptArrayToPlainArray($fullTypoScript['module.']['tx_maillogger.']);
        }

        return self::$currentModuleConfiguration[$key];
    }
}
