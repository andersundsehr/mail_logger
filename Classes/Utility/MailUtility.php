<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Utility;

use Exception;
use Pluswerk\MailLogger\Domain\Model\TemplateBasedMailMessage;
use Pluswerk\MailLogger\Domain\Repository\MailTemplateRepository;
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

        return $mail->setMailTemplate($mailTemplate, true, $viewParameters);
    }
}
