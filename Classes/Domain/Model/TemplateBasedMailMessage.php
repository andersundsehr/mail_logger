<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Domain\Model;

use Exception;
use Pluswerk\MailLogger\Service\MailTemplateContentTransformer;
use Override;
use Pluswerk\MailLogger\Utility\ConfigurationUtility;
use Symfony\Component\Mime\Crypto\DkimSigner;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;

class TemplateBasedMailMessage extends MailMessage
{
    protected MailTemplate $mailTemplate;

    /**
     * @var array<array-key, mixed>
     */
    protected array $viewParameters = [];

    protected string $typoScriptKey = '';

    protected ?string $messageTemplatePathAndFilename = null;

    /**
     * @var array<int|string, string>
     */
    protected array $partialRootPaths = [];

    /**
     * @var array<int|string, string>
     */
    protected array $layoutRootPaths = [];

    /**
     * @var array<array-key, mixed>
     */
    protected array $templateSettings = [];

    protected string $subjectTemplateSource = '';

    protected string $messageTemplateSource = '';

    public function __construct(
        protected readonly ViewFactoryInterface $viewFactory,
    ) {
        parent::__construct();
    }

    public function getMailTemplate(): MailTemplate
    {
        return $this->mailTemplate;
    }

    /**
     * @param array<array-key, mixed> $viewParameters This is necessary if you use Fluid for your mail fields
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
            $this->html($this->renderMessageBody());
        } catch (Exception $exception) {
            throw new Exception('Error while setting mail body template: ' . $exception->getMessage(), 1449133006, $exception);
        }

        try {
            $this->setSubject($this->renderSubject());
        } catch (Exception $exception) {
            throw new Exception('Error while setting mail subject template: ' . $exception->getMessage(), 1449133007, $exception);
        }

        $this->signMail();
        return parent::send();
    }

    private function signMail(): void
    {
        $settings = ConfigurationUtility::getCurrentModuleConfiguration('settings');
        if (isset($settings['dkim'][$this->mailTemplate->getDkimKey()])) {
            $conf = $settings['dkim'][$this->mailTemplate->getDkimKey()];

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

        $fromAddress = $this->getRenderedValue($values['mailFromAddress'] ?? '');
        if ($fromAddress !== '') {
            $fromName = $this->getRenderedValue($values['mailFromName'] ?? '');
            $fromName = $fromName ?: $fromAddress;
            $this->setFrom($this->cleanUpMailAddressesAndNames([$fromAddress => $fromName]));
        }

        $toAddresses = GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailToAddresses'] ?? ''), true);
        if ($toAddresses !== []) {
            $toNames = GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailToNames'] ?? ''));
            $combinedTo = [];
            foreach ($toAddresses as $key => $toAddress) {
                $combinedTo[$toAddress] = empty($toNames[$key]) ? '' : $toNames[$key];
            }

            $this->setTo($this->cleanUpMailAddressesAndNames($combinedTo));
        }

        if (!empty($values['mailCopyAddresses'])) {
            $this->setCc(GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailCopyAddresses']), true));
        }

        if (!empty($values['mailBlindCopyAddresses'])) {
            $this->setBcc(GeneralUtility::trimExplode(',', $this->getRenderedValue($values['mailBlindCopyAddresses']), true));
        }

        $this->assignMailTemplatePaths($values);

        if (!empty($values['subject'])) {
            $this->subjectTemplateSource = $values['subject'];
        }

        if (!empty($values['message'])) {
            $message = GeneralUtility::makeInstance(MailTemplateContentTransformer::class)->transform((string)$values['message']);
            $this->messageTemplateSource = $message;
        }
    }

    /**
     * @param array<string, string|null> $addressesAndNames
     * @return array<int|string, string>
     */
    private function cleanUpMailAddressesAndNames(array $addressesAndNames): array
    {
        $cleanedAddressesAndNames = [];
        foreach ($addressesAndNames as $mailAddress => $name) {
            if ($name === null || $name === '') {
                $cleanedAddressesAndNames[] = $mailAddress;
                continue;
            }

            $cleanedAddressesAndNames[$mailAddress] = $name;
        }

        return $cleanedAddressesAndNames;
    }

    private function getRenderedValue(string $value): string
    {
        if ($value !== '' && (str_contains($value, '{') || str_contains($value, '<'))) {
            return $this->renderTemplateSource($value, $this->viewParameters);
        }

        return $value;
    }

    private function renderMessageBody(): string
    {
        $variables = $this->viewParameters;
        $variables['mailTemplate'] = $this->mailTemplate;
        if ($this->messageTemplateSource !== '') {
            $variables['message'] = $this->renderTemplateSource($this->messageTemplateSource, $this->viewParameters);
        }

        $view = $this->viewFactory->create(new ViewFactoryData(
            partialRootPaths: $this->partialRootPaths,
            layoutRootPaths: $this->layoutRootPaths,
            templatePathAndFilename: $this->messageTemplatePathAndFilename
        ));
        $view->assignMultiple($variables);
        if ($this->templateSettings !== []) {
            $view->assign('settings', $this->templateSettings);
        }

        return $view->render();
    }

    private function renderSubject(): string
    {
        return $this->subjectTemplateSource !== ''
            ? $this->renderTemplateSource($this->subjectTemplateSource, $this->viewParameters)
            : ($this->getSubject() ?? '');
    }

    /**
     * @param array<array-key, mixed> $variables
     */
    private function renderTemplateSource(string $templateSource, array $variables): string
    {
        $view = $this->viewFactory->create(new ViewFactoryData());
        if (!$view instanceof FluidViewAdapter) {
            throw new Exception('TemplateBasedMailMessage requires a Fluid view adapter.', 1720965301);
        }

        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateSource);
        return $view->assignMultiple($variables)->render();
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private function assignMailTemplatePaths(array $values): void
    {
        if ($this->messageTemplatePathAndFilename === null) {
            $this->partialRootPaths = $this->normalizeRootPaths(
                $values['defaultTemplatePaths']['partialRootPaths'] ?? [],
                $values['templatePaths']['partialRootPaths'] ?? [],
            );
            $this->layoutRootPaths = $this->normalizeRootPaths(
                $values['defaultTemplatePaths']['layoutRootPaths'] ?? [],
                $values['templatePaths']['layoutRootPaths'] ?? [],
            );
            $templatePath = $values['templatePaths']['templatePath'] ?: $values['defaultTemplatePaths']['templatePath'];
            $this->messageTemplatePathAndFilename = GeneralUtility::getFileAbsFileName($templatePath);
            $this->templateSettings = is_array($values['templatePaths']['settings'] ?? null)
                ? $values['templatePaths']['settings']
                : [];
        }
    }

    /**
     * @return array<int|string, string>
     */
    private function normalizeRootPaths(mixed ...$paths): array
    {
        $normalizedPaths = [];
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                $normalizedPaths[] = $path;
                continue;
            }

            if (!is_array($path)) {
                continue;
            }

            foreach ($path as $key => $value) {
                if (is_string($value) && $value !== '') {
                    $normalizedPaths[$key] = $value;
                }
            }
        }

        return $normalizedPaths;
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
