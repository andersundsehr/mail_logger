<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Domain\Model;

use Exception;
use Override;
use Pluswerk\MailLogger\Utility\ConfigurationUtility;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Crypto\DkimSigner;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\StandaloneView;

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

    protected ?StandaloneView $legacyMessageView = null;

    protected ?StandaloneView $legacySubjectView = null;

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
     * @deprecated Will be removed in a future version. TemplateBasedMailMessage no longer uses StandaloneView internally.
     */
    public function getMessageView(): StandaloneView
    {
        $this->triggerStandaloneViewDeprecation(__METHOD__);
        $this->legacyMessageView ??= GeneralUtility::makeInstance(StandaloneView::class);
        return $this->legacyMessageView;
    }

    /**
     * @deprecated Will be removed in a future version. Configure the mail template paths instead of injecting StandaloneView.
     */
    public function setMessageView(StandaloneView $messageView): self
    {
        $this->triggerStandaloneViewDeprecation(__METHOD__);
        $this->legacyMessageView = $messageView;
        return $this;
    }

    /**
     * @deprecated Will be removed in a future version. TemplateBasedMailMessage no longer uses StandaloneView internally.
     */
    public function getSubjectView(): StandaloneView
    {
        $this->triggerStandaloneViewDeprecation(__METHOD__);
        $this->legacySubjectView ??= GeneralUtility::makeInstance(StandaloneView::class);
        return $this->legacySubjectView;
    }

    /**
     * @deprecated Will be removed in a future version. Set the subject template source through the mail template configuration.
     */
    public function setSubjectView(StandaloneView $subjectView): self
    {
        $this->triggerStandaloneViewDeprecation(__METHOD__);
        $this->legacySubjectView = $subjectView;
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
        if (isset($settings['dkim']) && isset($settings['dkim'][$this->mailTemplate->getDkimKey()])) {
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
            if ($this->legacySubjectView instanceof StandaloneView) {
                $this->legacySubjectView->setTemplateSource($values['subject']);
            }
        }

        if (!empty($values['message'])) {
            $this->messageTemplateSource = $values['message'];
        }
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

    private function getRenderedValue(string $value): string
    {
        if ($value !== '' && (str_contains($value, '{') || str_contains($value, '<'))) {
            return $this->renderTemplateSource($value, $this->viewParameters);
        }

        return $value;
    }

    private function renderMessageBody(): string
    {
        if ($this->legacyMessageView instanceof StandaloneView) {
            $this->legacyMessageView->assign('mailTemplate', $this->mailTemplate);
            return $this->renderLegacyView($this->legacyMessageView);
        }

        $variables = $this->viewParameters;
        $variables['mailTemplate'] = $this->mailTemplate;
        if ($this->messageTemplateSource !== '') {
            $variables['message'] = $this->renderTemplateSource($this->messageTemplateSource, $this->viewParameters);
        }

        $view = $this->viewFactory->create(new ViewFactoryData(
            partialRootPaths: $this->partialRootPaths,
            layoutRootPaths: $this->layoutRootPaths,
            templatePathAndFilename: $this->messageTemplatePathAndFilename,
            request: $this->getRequest(),
        ));
        $view->assignMultiple($variables);
        if ($this->templateSettings !== []) {
            $view->assign('settings', $this->templateSettings);
        }

        return $view->render();
    }

    private function renderSubject(): string
    {
        if ($this->legacySubjectView instanceof StandaloneView) {
            return $this->renderLegacyView($this->legacySubjectView);
        }

        return $this->subjectTemplateSource !== ''
            ? $this->renderTemplateSource($this->subjectTemplateSource, $this->viewParameters)
            : $this->getSubject();
    }

    /**
     * @param array<array-key, mixed> $variables
     */
    private function renderTemplateSource(string $templateSource, array $variables): string
    {
        $view = $this->viewFactory->create(new ViewFactoryData(request: $this->getRequest()));
        if (!$view instanceof FluidViewAdapter) {
            throw new Exception('TemplateBasedMailMessage requires a Fluid view adapter.', 1720965301);
        }

        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateSource);
        return $view->assignMultiple($variables)->render();
    }

    private function renderLegacyView(StandaloneView $view): string
    {
        $view->setRequest($this->getRequest());
        return $view->assignMultiple($this->viewParameters)->render();
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

        if ($this->legacyMessageView instanceof StandaloneView && !$this->legacyMessageView->getPartialRootPaths()) {
            $this->legacyMessageView->setPartialRootPaths($this->partialRootPaths);
            $this->legacyMessageView->setTemplatePathAndFilename($this->messageTemplatePathAndFilename);
            $this->legacyMessageView->setLayoutRootPaths($this->layoutRootPaths);
            if ($this->templateSettings !== []) {
                $this->legacyMessageView->assignMultiple(['settings' => $this->templateSettings]);
            }
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

    private function getRequest(): ServerRequestInterface
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST'];
        }

        return GeneralUtility::makeInstance(ServerRequestFactory::class)
            ->createServerRequest('GET', 'https://localhost/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    public function getTypoScriptKey(): string
    {
        return $this->typoScriptKey;
    }

    private function setTypoScriptKey(string $typoScriptKey): void
    {
        $this->typoScriptKey = $typoScriptKey;
    }

    private function triggerStandaloneViewDeprecation(string $method): void
    {
        trigger_error(
            $method . ' is deprecated and will be removed in a future version. '
            . 'TemplateBasedMailMessage renders Fluid templates through TYPO3 ViewFactoryInterface now.',
            E_USER_DEPRECATED
        );
    }
}
