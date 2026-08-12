<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

$ttContentColumns = [
    'imagelazy64' => [
        'exclude' => true,
        'label' => 'LLL:EXT:photoswipe/Resources/Private/Language/locallang_db.xlf:tx_photoswipe.imagelazy64',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            // Associative keys since TYPO3 12. The numeric format is no longer
            // migrated automatically in v14.
            'items' => [
                [
                    'label' => '',
                    'value' => '',
                ],
            ],
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns('tt_content', $ttContentColumns);
ExtensionManagementUtility::addFieldsToPalette('tt_content', 'imagelinks', 'imagelazy64');
