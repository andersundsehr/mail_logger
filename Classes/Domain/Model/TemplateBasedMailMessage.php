<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Domain\Model;

use Override;
use Exception;
use Pluswerk\MailLogger\Service\MailTemplateContentTransformer;
use Pluswerk\MailLogger\Utility\ConfigurationUtility;
use Symfony\Component\Mime\Crypto\DkimSigner;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

use function array_filter;

class TemplateBasedMailMessage extends MailMessage
{
    protected MailTemplate $mailTemplate;

    /**
     * @var array<array-key, mixed>
     */
    protected array $viewParameters = [];

    protected string $typoScriptKey = '';

    public function __construct(
        protected StandaloneView $messageView,
        protected StandaloneView $subjectView
    ) {
        parent::__construct();
    }

    public function getMailTemplate(): MailTemplate
    {
        return $this->mailTemplate;
    }

    /**
     * @param array<array-key, mixed> $viewParameters This is necessary if you use Fluid for your mail fields
     * @return $this
     */
    public function setMailTemplate(MailTemplate $mailTemplate, bool $assignMailTemplate = true, array $viewParameters = []): self
    {
        $this->mailTemplate = $mailTemplate;
        if ($viewParameters !== []) {
            $this->setViewParameters($viewParameters);
        }

        if ($assignMailTemplate) {
            $this->assignMailTemplate();
        }

        return $this;
    }

    public function getMessageView(): StandaloneView
    {
        return $this->messageView;
    }

    public function setMessageView(StandaloneView $messageView): self
    {
        $this->messageView = $messageView;
        return $this;
    }

    public function getSubjectView(): StandaloneView
    {
        return $this->subjectView;
    }

    public function setSubjectView(StandaloneView $subjectView): self
    {
        $this->subjectView = $subjectView;
        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getViewParameters(): array
    {
        return $this->viewParameters;
    }

    /**
     * @param array<array-key, mixed> $viewParameters
     */
    public function setViewParameters(array $viewParameters): self
    {
        $this->viewParameters = $viewParameters;
        return $this;
    }

    public function assignMailTemplate(): void
    {
        $this->assignMailTemplateValues($this->mailTemplate->_getProperties());
    }

    public function assignDefaultsFromTypoScript(string $typoScriptKey, string $templatePathKey): void
    {
        if ($typoScriptKey !== '') {
            $this->setTypoScriptKey($typoScriptKey);
            $settings = ConfigurationUtility::getCurrentModuleConfiguration('settings');
            $concreteSettings = $settings['mailTemplates'][$typoScriptKey];
            $concreteSettings['templatePaths'] = $settings['templateOverrides'][$templatePathKey ?: 'default'];
            $concreteSettings['defaultTemplatePaths'] = $settings['templateOverrides']['default'];
            $this->assignMailTemplateValues($concreteSettings);
        }
    }

    #[Override]
    public function send(): bool
    {
        try {
            $body = $this->renderView($this->messageView);
            $this->html($body);
        } catch (Exception $exception) {
            throw new Exception('Error while setting mail body template: ' . $exception->getMessage(), 1449133006, $exception);
        }

        try {
            $this->setSubject($this->renderView($this->subjectView));
        } catch (Exception $exception) {
            throw new Exception('Error while setting mail subject template: ' . $exception->getMessage(), 1449133007, $exception);
        }

        $this->signMail();
        return parent::send();
    }

    private function signMail(): void
    {
        $settings = ConfigurationUtility::getCurrentModuleConfiguration('settings');
        if (isset($settings['dkim']) && isset($settings['dkim'][$this->mailTemplate->getDkimKey()])) {
            $conf = $settings['dkim'][$this->mailTemplate->getDkimKey()];

            // needs testing:
            $signer = new DkimSigner($this->formPrivateKey($conf['key']), $conf['domain'], $conf['selector'], [
                'headers_to_ignore' => [
                    'Return-Path',
                ],
            ]);
            $signedMail = $signer->sign($this);
            $this->setHeaders($signedMail->getHeaders());
            $this->setBody($signedMail->getBody());
        }
    }


    private function formPrivateKey(string $key): string
    {
        $begin = '-----BEGIN RSA PRIVATE KEY-----';
        $ending = '-----END RSA PRIVATE KEY-----';
        return $begin . PHP_EOL . trim($key) . PHP_EOL . $ending;
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private function assignMailTemplateValues(array $values): void
    {
        if (!empty($values['typoScriptKey'])) {
            $this->assignDefaultsFromTypoScript($values['typoScriptKey'], $this->mailTemplate->getTemplatePathKey());
        }

        // set From
        $fromAddress = $this->getRenderedValue($values['mailFromAddress'] ?? '');
        if ($fromAddress !== '') {
            $fromName = $this->getRenderedValue($values['mailFromName'] ?? '');
            $fromName = $fromName ?: $fromAddress;
            $this->setFrom($this->cleanUpMailAddressesAndNames([$fromAddress => $fromName]));
        }

        // set To
        $toAddresses = GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailToAddresses'] ?? ''), true);
        if ($toAddresses !== []) {
            $toNames = GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailToNames'] ?? ''));
            $combinedTo = [];
            foreach ($toAddresses as $key => $toAddress) {
                $combinedTo[$toAddress] = empty($toNames[$key]) ? '' : $toNames[$key];
            }

            $this->setTo($this->cleanUpMailAddressesAndNames($combinedTo));
        }

        // set CC and BCC
        if (!empty($values['mailCopyAddresses'])) {
            $this->setCc(GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailCopyAddresses']), true));
        }

        if (!empty($values['mailBlindCopyAddresses'])) {
            $this->setBcc(GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailBlindCopyAddresses']), true));
        }

        $this->assignMailTemplatePaths($values);

        // set subject and message
        if (!empty($values['subject'])) {
            $this->subjectView->setTemplateSource($values['subject']);
        }

        if (!empty($values['message'])) {
            $mailView = GeneralUtility::makeInstance(StandaloneView::class);
            $message = GeneralUtility::makeInstance(MailTemplateContentTransformer::class)->transform((string)$values['message']);
            $mailView->setTemplateSource($message);
            $mailView->assignMultiple($this->viewParameters);
            $this->messageView->assign('message', $mailView->render());
        }

        $this->messageView->assign('mailTemplate', $this->mailTemplate);
    }

    /**
     * @param array<string, string|null> $addressesAndNames
     * @return array<array-key, string>
     */
    private function cleanUpMailAddressesAndNames(array $addressesAndNames): array
    {
        foreach ($addressesAndNames as $mailAddress => $name) {
            if (!$name && is_string($mailAddress)) {
                unset($addressesAndNames[$mailAddress]);
                $addressesAndNames[] = $mailAddress;
            }
        }

        return $addressesAndNames;
    }

    /**
     * Short method to render a standalone fluid template
     */
    private function getRenderedValue(string $value): string
    {
        // Check if the string is not empty and contains any Fluid stuff
        if ($value !== '' && (str_contains($value, '{') || str_contains($value, '<'))) {
            /** @var StandaloneView $valueView */
            $valueView = GeneralUtility::makeInstance(StandaloneView::class);
            $valueView->setTemplateSource($value);
            $value = $this->renderView($valueView);
        }

        return $value;
    }

    /**
     * Render view with all parameters
     */
    private function renderView(StandaloneView $view): string
    {
        return $view->assignMultiple($this->viewParameters)->render();
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private function assignMailTemplatePaths(array $values): void
    {
        if (!$this->messageView->getPartialRootPaths()) {
            $this->messageView->setPartialRootPaths(
                array_filter(
                    [
                        $values['defaultTemplatePaths']['partialRootPaths'],
                        $values['templatePaths']['partialRootPaths'] ?? [],
                    ]
                )
            );

            $this->messageView->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName($values['templatePaths']['templatePath'] ?: $values['defaultTemplatePaths']['templatePath'])
            );

            $this->messageView->setLayoutRootPaths(
                array_filter(
                    [
                        $values['defaultTemplatePaths']['layoutRootPaths'],
                        $values['templatePaths']['layoutRootPaths'] ?? [],
                    ]
                )
            );

            if (isset($values['templatePaths']['settings']) && !empty($values['templatePaths']['settings'])) {
                $this->messageView->assignMultiple(['settings' => $values['templatePaths']['settings']]);
            }
        }
    }

    public function getTypoScriptKey(): string
    {
        return $this->typoScriptKey;
    }

    private function setTypoScriptKey(string $typoScriptKey): void
    {
        $this->typoScriptKey = $typoScriptKey;
    }
}
