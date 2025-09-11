<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {
    // Add TypoScript

    ExtensionManagementUtility::addStaticFile(
        'mail_logger',
        'Configuration/TypoScript',
        'Mail Logger by anders und sehr GmbH'
    );
})();
