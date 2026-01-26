<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\EventListener;

use Pluswerk\MailLogger\Service\CleanupService;
use Throwable;

/**
 * Event listener that triggers mail log cleanup on frontend and backend requests.
 * Uses cache-based locking to prevent parallel runs and throttle execution.
 */
final readonly class CleanupEventListener
{
    public function __construct(
        private CleanupService $cleanupService,
    ) {
    }

    public function __invoke(): void
    {
        try {
            $this->cleanupService->tryRunCleanup();
        } catch (Throwable) {
            // Silently ignore to not break page rendering
            // CleanupService already logs the error
        }
    }
}
