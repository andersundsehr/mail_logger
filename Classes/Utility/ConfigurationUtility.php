<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Utility;

use Exception;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;

class ConfigurationUtility
{
    /** @var array<array-key, mixed> */
    protected static array $currentModuleConfiguration = [];

    public function __construct(
        private readonly BackendConfigurationManager $backendConfigurationManager,
        private readonly TypoScriptService $typoScriptService
    ) {
    }

    /**
     * @return array<array-key, mixed>
     * @throws ReflectionException
     */
    public static function getCurrentModuleConfiguration(string $key): array
    {
        return GeneralUtility::makeInstance(self::class)->getConfiguration($key);
    }

    /**
     * @return array<array-key, mixed>
     * @throws ReflectionException
     */
    public function getConfiguration(string $key): array
    {
        if (!self::$currentModuleConfiguration) {
            // we always use the BackendConfigurationManager, because flux is overwriting the ConfigurationManager
            // and always uses the FrontendConfigurationManager instead of the correct one for the current context

            // TYPO3 v13 requires $request parameter, v12 does not accept it
            $reflection = new ReflectionMethod($this->backendConfigurationManager, 'getTypoScriptSetup');
            if ($reflection->getNumberOfParameters() > 0) {
                // v13: pass request parameter
                $request = $GLOBALS['TYPO3_REQUEST'] ?? throw new Exception('No $GLOBALS[\'TYPO3_REQUEST\'] found', 1688138054);
                /** @phpstan-ignore-next-line arguments.count - v13 compatibility */
                $fullTypoScript = $this->backendConfigurationManager->getTypoScriptSetup($request);
            } else {
                // v12: no parameters
                /** @phpstan-ignore-next-line arguments.count - v12 compatibility */
                $fullTypoScript = $this->backendConfigurationManager->getTypoScriptSetup();
            }

            if (empty($fullTypoScript['module.']['tx_maillogger.'])) {
                throw new RuntimeException('Constants and setup TypoScript are not included!', 7780827935);
            }

            self::$currentModuleConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($fullTypoScript['module.']['tx_maillogger.']);
        }

        return self::$currentModuleConfiguration[$key];
    }
}
