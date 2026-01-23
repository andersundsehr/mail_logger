<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\EventListener;

use Pluswerk\MailLogger\Service\CleanupService;
use Throwable;
use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

/**
 * Event listener that triggers mail log cleanup on frontend and backend requests.
 * Uses cache-based locking to prevent parallel runs and throttle execution.
 */
final readonly class CleanupEventListener
{
    public function __construct(
        private CleanupService $cleanupService,
    ) {}

    /**
     * Triggered on frontend page render
     */
    public function onFrontendRender(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $this->runCleanup();
    }

    /**
     * Triggered on backend page render
     */
    public function onBackendRender(AfterBackendPageRenderEvent $event): void
    {
        $this->runCleanup();
    }

    private function runCleanup(): void
    {
        try {
            $this->cleanupService->tryRunCleanup();
        } catch (Throwable) {
            // Silently ignore to not break page rendering
            // CleanupService already logs the error
        }
    }
}
