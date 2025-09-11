<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Domain\Model;

use Pluswerk\MailLogger\Dto\MailStatus;

class MailLog extends AbstractModel
{
    final public const int MEBI_BYTE = 1048576;

    protected string $typoScriptKey = '';

    protected string $subject = '';

    protected string $message = '';

    /**
     * json encoded
     */
    protected string $mailFrom = '';

    /**
     * json encoded
     */
    protected string $mailTo = '';

    /**
     * json encoded
     */
    protected string $mailCopy = '';

    /**
     * json encoded
     */
    protected string $mailBlindCopy = '';

    protected string $headers = '';

    protected string $result = 'Not send until now';

    //protected int $status = MailStatus::UNKNOWN->value; // if PHP 8.2 is lowest PHP version
    protected int $status = 0;

    protected string $debug = '';

    protected int $sysLanguageUid = 0;

    public function getTypoScriptKey(): string
    {
        return $this->typoScriptKey;
    }

    public function setTypoScriptKey(string $typoScriptKey): self
    {
        $this->typoScriptKey = $typoScriptKey;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        if (strlen($this->message) > self::MEBI_BYTE) {
            $this->message = substr($this->message, 0, self::MEBI_BYTE);
            $this->message .= ' (... message trimmed ...)';
        }

        return $this;
    }

    public function getMailFrom(): string
    {
        return $this->mailFrom;
    }

    public function setMailFrom(string $mailFrom): self
    {
        $this->mailFrom = $mailFrom;
        return $this;
    }

    public function getMailTo(): string
    {
        return $this->mailTo;
    }

    public function setMailTo(string $mailTo): self
    {
        $this->mailTo = $mailTo;
        return $this;
    }

    public function getMailCopy(): string
    {
        return $this->mailCopy;
    }

    public function setMailCopy(string $mailCopy): self
    {
        $this->mailCopy = $mailCopy;
        return $this;
    }

    public function getMailBlindCopy(): string
    {
        return $this->mailBlindCopy;
    }

    public function setMailBlindCopy(string $mailBlindCopy): self
    {
        $this->mailBlindCopy = $mailBlindCopy;
        return $this;
    }

    public function getHeaders(): string
    {
        return $this->headers;
    }

    public function setHeaders(string $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): self
    {
        $this->result = $result;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDebug(): string
    {
        return $this->debug;
    }

    public function setDebug(string $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    public function getSysLanguageUid(): int
    {
        return $this->sysLanguageUid;
    }

    public function setSysLanguageUid(int $sysLanguageUid): self
    {
        $this->sysLanguageUid = $sysLanguageUid;
        return $this;
    }
}
