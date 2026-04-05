<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Photoswipe',
    'description' => 'Enables Photoswipe for TYPO3\'s native image enlargement. Small js-lib and easy to install. It just works with plain javascript, no jQuery or other frameworks are needed.',
    'author' => 'Tobi Eichelberger',
    'author_company' => '',
    'author_email' => 'tobi.eichelberger@gmail.com',
    'category' => 'fe',
    'clearCacheOnLoad' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.2.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'stable',
    'version' => '2.0.15',
];
