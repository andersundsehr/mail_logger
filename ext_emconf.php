<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Mail Logger by anders und sehr GmbH',
    'description' => 'This extension logs all your outgoing mails and provides email templates and debugging tools',
    'category' => 'module',
    'author' => 'Matthias Vogel',
    'author_email' => 'm.vogel@andersundsehr.com',
    'state' => 'stable',
    'version' => \Composer\InstalledVersions::getPrettyVersion('pluswerk/mail-logger'),
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
