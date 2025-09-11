<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Dto;

use Symfony\Component\Mailer\SentMessage;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;

final readonly class SendResult
{
    public function __construct(
        public string $result,
        public MailStatus $status,
        public string $debug = '',
        public ?SentMessage $sentMessage = null,
        public ?Throwable $throwable = null,
    ) {
    }

    public function getDebugMessage(): string
    {
        $result = '';
        if ($this->debug) {
            $result .= '# Debug: ' . PHP_EOL . $this->debug . PHP_EOL;
        }

        if ($this->throwable) {
            $result .= '# Exception: ' . PHP_EOL . $this->throwable->getMessage() . PHP_EOL;
            if (!Environment::getContext()->isProduction()) {
                $result .= $this->throwable->getTraceAsString() . PHP_EOL;
            }
        }

        return $result;
    }
}
