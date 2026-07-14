<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Service;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;

class CleanupSettingsService
{
    private const string DEFAULT_LIFETIME = '30 days';

    private const string DEFAULT_ANONYMIZE_AFTER = '7 days';

    private const string ANONYMIZE_SYMBOL = '***';

    private bool $loaded = false;

    private string $lifetime = self::DEFAULT_LIFETIME;

    private bool $anonymize = true;

    private string $anonymizeAfter = self::DEFAULT_ANONYMIZE_AFTER;

    public function __construct(
        private readonly ConfigurationManagerInterface $configurationManager,
    ) {
    }

    public function isLoaded(): bool
    {
        $this->loadSettings();
        return $this->loaded;
    }

    public function getLifetime(): string
    {
        $this->loadSettings();
        return $this->lifetime;
    }

    public function shouldAnonymize(): bool
    {
        $this->loadSettings();
        return $this->anonymize;
    }

    public function getAnonymizeAfter(): string
    {
        $this->loadSettings();
        return $this->anonymizeAfter;
    }

    public function getAnonymizeSymbol(): string
    {
        return self::ANONYMIZE_SYMBOL;
    }

    private function loadSettings(): void
    {
        if ($this->loaded) {
            return;
        }

        $fullSettings = $this->getFullTypoScriptSettings();

        $settings = $fullSettings['module.']['tx_maillogger.']['settings.'] ?? [];

        $this->lifetime = $settings['cleanup.']['lifetime'] ?? self::DEFAULT_LIFETIME;
        $this->anonymize = (bool)($settings['cleanup.']['anonymize'] ?? true);
        $this->anonymizeAfter = $settings['cleanup.']['anonymizeAfter'] ?? self::DEFAULT_ANONYMIZE_AFTER;

        $this->loaded = true;
    }

    /**
     * @return mixed[]
     */
    private function getFullTypoScriptSettings(): array
    {
        try {
            return $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
            );
        } catch (NoServerRequestGivenException) {
            $this->configurationManager->setRequest($this->createBackendRequest());
        }

        return $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
    }

    private function createBackendRequest(): ServerRequestInterface
    {
        return GeneralUtility::makeInstance(ServerRequestFactory::class)
            ->createServerRequest('GET', 'https://localhost/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }
}
