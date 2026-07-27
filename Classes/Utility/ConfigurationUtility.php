<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Utility;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
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
     * @throws RuntimeException
     */
    public static function getCurrentModuleConfiguration(string $key): array
    {
        return GeneralUtility::makeInstance(self::class)->getConfiguration($key);
    }

    /**
     * @return array<array-key, mixed>
     * @throws RuntimeException
     */
    public function getConfiguration(string $key): array
    {
        if (!self::$currentModuleConfiguration) {
            // we always use the BackendConfigurationManager, because flux is overwriting the ConfigurationManager
            // and always uses the FrontendConfigurationManager instead of the correct one for the current context
            $request = $this->getRequest();
            $fullTypoScript = $this->backendConfigurationManager->getTypoScriptSetup($request);
            if (empty($fullTypoScript['module.']['tx_maillogger.'])) {
                throw new RuntimeException('Constants and setup TypoScript are not included!', 7780827935);
            }

            self::$currentModuleConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($fullTypoScript['module.']['tx_maillogger.']);
        }

        return self::$currentModuleConfiguration[$key];
    }

    /**
     * @deprecated Will replace by SiteSet
     */
    private function getRequest(): ServerRequestInterface
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST'];
        }

        return GeneralUtility::makeInstance(ServerRequestFactory::class)
            ->createServerRequest('GET', 'https://localhost/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }
}
