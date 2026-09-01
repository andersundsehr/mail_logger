<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Utility;

use Exception;
use Pluswerk\MailLogger\Domain\Model\TemplateBasedMailMessage;
use Pluswerk\MailLogger\Domain\Repository\MailTemplateRepository;
use Pluswerk\MailLogger\Service\MailTemplateContentTransformer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MailUtility
{
    /**
     * Shortcut to send mails
     * \Pluswerk\MailLogger\Utility\MailUtility::getMailByKey('exampleReport', null, ['var' => $var])->send();
     *
     * @param string $key The TypoScript key of your template
     * @param int|null $languageUid The language uid
     * @param array<array-key, mixed> $viewParameters This is necessary if you use Fluid for your mail fields
     * @throws Exception
     */
    public static function getMailByKey(string $key, ?int $languageUid = null, array $viewParameters = []): TemplateBasedMailMessage
    {
        $mail = GeneralUtility::makeInstance(TemplateBasedMailMessage::class);
        $templateRepository = GeneralUtility::makeInstance(MailTemplateRepository::class);
        $mailTemplate = $templateRepository->findOneByTypoScriptKeyAndLanguage($key, $languageUid);
        if (!$mailTemplate) {
            throw new Exception('No "MailTemplate" was found for key "' . $key . '". Please check your database records!', 6640694639);
        }

        $mailTemplate = GeneralUtility::makeInstance(MailTemplateContentTransformer::class)->transformMailTemplate($mailTemplate);

        return $mail->setMailTemplate($mailTemplate, true, $viewParameters);
    }

    /**
     * Creates a mail from the exact mail-template record selected by its UID.
     *
     * Editors can create multiple mail-template records with the same TypoScript key.
     * A key-based lookup cannot determine which of those records an editor selected
     * in another record, such as a TYPO3 form finisher; it may use the first matching
     * template instead. getMailByKey() remains the standard way to choose a template.
     * Use this method only when the calling context stores a concrete selection as a
     * UID.
     *
     * @param int $mailTemplateId The UID of the selected mail-template record
     * @param array<array-key, mixed> $viewParameters This is necessary if you use Fluid for your mail fields
     * @throws Exception
     */
    public static function getMailById(int $mailTemplateId, array $viewParameters = []): TemplateBasedMailMessage
    {
        $mail = GeneralUtility::makeInstance(TemplateBasedMailMessage::class);
        $templateRepository = GeneralUtility::makeInstance(MailTemplateRepository::class);
        $mailTemplate = $templateRepository->findByUid($mailTemplateId);
        if (!$mailTemplate) {
            throw new Exception('No "MailTemplate" was found for uid "' . $mailTemplateId . '". Please check your database records!', 6976725035);
        }

        $mailTemplate = GeneralUtility::makeInstance(MailTemplateContentTransformer::class)->transformMailTemplate($mailTemplate);

        return $mail->setMailTemplate($mailTemplate, true, $viewParameters);
    }
}
