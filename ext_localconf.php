<?php

declare(strict_types=1);

use Pluswerk\MailLogger\Logging\MailerExtender;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

// Add default TypoScript
ExtensionManagementUtility::addTypoScriptConstants(
    "@import 'EXT:mail_logger/Configuration/TypoScript/constants.typoscript'"
);
ExtensionManagementUtility::addTypoScriptSetup(
    "@import 'EXT:mail_logger/Configuration/TypoScript/setup.typoscript'"
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Mailer::class] = [
    'className' => MailerExtender::class,
];
