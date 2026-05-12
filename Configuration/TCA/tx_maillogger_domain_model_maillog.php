<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog',
        'label' => 'mail_to',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'hideTable' => true,

        'languageField' => 'sys_language_uid',
        'enablecolumns' => [],
        'searchFields' => 'typo_script_key,subject,message,mail_from,mail_to,mail_copy,mail_blind_copy,',
        'iconfile' => 'EXT:mail_logger/Resources/Public/Icons/MailLog.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid,typo_script_key,subject,message,mail_from,mail_to,mail_copy,mail_blind_copy,result,debug'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language'],
        ],
        'tstamp' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'size' => 13,
                'checkbox' => 0,
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'size' => 13,
                'checkbox' => 0,
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'typo_script_key' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.typo_script_key',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'subject' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.subject',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'message' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.message',
            'config' => [
                'readOnly' => 1,
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
        ],
        'mail_from' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.mail_from',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'mail_to' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.mail_to',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'mail_copy' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.mail_copy',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'mail_blind_copy' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.mail_blind_copy',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
            ],
        ],
        'result' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.result',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.status',
            'config' => [
                'readOnly' => 1,
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'debug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.debug',
            'config' => [
                'readOnly' => 1,
                'type' => 'text',
            ],
        ],
        'headers' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mail_logger/Resources/Private/Language/locallang_db.xlf:tx_maillogger_domain_model_maillog.headers',
            'config' => [
                'readOnly' => 1,
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
        ],

    ],
];
