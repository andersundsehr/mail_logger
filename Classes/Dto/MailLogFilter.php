<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Dto;

final readonly class MailLogFilter
{
    private string $mailTo;

    private string $mailFrom;

    private string $subject;

    public function __construct(
        string $mailTo = '',
        string $mailFrom = '',
        string $subject = '',
        private ?MailStatus $status = null,
    ) {
        $this->mailTo = trim($mailTo);
        $this->mailFrom = trim($mailFrom);
        $this->subject = trim($subject);
    }

    public function getMailTo(): string
    {
        return $this->mailTo;
    }

    public function getMailFrom(): string
    {
        return $this->mailFrom;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getStatus(): ?MailStatus
    {
        return $this->status;
    }

    public function getStatusValue(): string
    {
        return $this->status === null ? '' : (string)$this->status->value;
    }

    /**
     * @return array{
     *     mailTo: string,
     *     mailFrom: string,
     *     subject: string,
     *     status: string
     * }
     */
    public function toArray(): array
    {
        return [
            'mailTo' => $this->mailTo,
            'mailFrom' => $this->mailFrom,
            'subject' => $this->subject,
            'status' => $this->getStatusValue(),
        ];
    }
}
