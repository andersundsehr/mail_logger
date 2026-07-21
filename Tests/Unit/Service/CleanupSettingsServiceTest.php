<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Pluswerk\MailLogger\Service\CleanupSettingsService;
use Pluswerk\MailLogger\Utility\ConfigurationUtility;
use RuntimeException;

class CleanupSettingsServiceTest extends TestCase
{
    public function testReturnsConfiguredCleanupSettings(): void
    {
        $configurationUtility = $this->createMock(ConfigurationUtility::class);
        $configurationUtility->expects(self::once())
            ->method('getConfiguration')
            ->with('settings')
            ->willReturn([
                'cleanup' => [
                    'lifetime' => '90 days',
                    'anonymize' => false,
                    'anonymizeAfter' => '14 days',
                ],
            ]);

        $subject = new CleanupSettingsService($configurationUtility);

        self::assertTrue($subject->isLoaded());
        self::assertSame('90 days', $subject->getLifetime());
        self::assertFalse($subject->shouldAnonymize());
        self::assertSame('14 days', $subject->getAnonymizeAfter());
        self::assertSame('***', $subject->getAnonymizeSymbol());
    }

    public function testReturnsDefaultsWhenConfigurationCannotBeLoaded(): void
    {
        $configurationUtility = $this->createMock(ConfigurationUtility::class);
        $configurationUtility->expects(self::once())
            ->method('getConfiguration')
            ->with('settings')
            ->willThrowException(new RuntimeException());

        $subject = new CleanupSettingsService($configurationUtility);

        self::assertTrue($subject->isLoaded());
        self::assertSame('30 days', $subject->getLifetime());
        self::assertTrue($subject->shouldAnonymize());
        self::assertSame('7 days', $subject->getAnonymizeAfter());
    }
}
