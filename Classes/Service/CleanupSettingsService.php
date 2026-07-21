<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Service;

use Pluswerk\MailLogger\Utility\ConfigurationUtility;
use RuntimeException;

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
        private readonly ConfigurationUtility $configurationUtility,
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

        try {
            $settings = $this->configurationUtility->getConfiguration('settings');
        } catch (RuntimeException) {
            $settings = [];
        }

        $cleanupSettings = $settings['cleanup'] ?? [];

        $this->lifetime = $cleanupSettings['lifetime'] ?? self::DEFAULT_LIFETIME;
        $this->anonymize = (bool)($cleanupSettings['anonymize'] ?? true);
        $this->anonymizeAfter = $cleanupSettings['anonymizeAfter'] ?? self::DEFAULT_ANONYMIZE_AFTER;

        $this->loaded = true;
    }
}
