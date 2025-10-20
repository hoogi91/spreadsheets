<?php
$EM_CONF[$_EXTKEY] = [
    'title'        => 'Spreadsheets',
    'description'  => 'Extension to add field definition and plugin to show and select information from spreadsheets',
    'category'     => 'be',
    'author'       => 'Thorsten Hogenkamp',
    'author_email' => 'thorsten@hogenkamp-bocholt.de',
    'version'      => '4.0.3',
    'state'        => 'stable',
    'constraints'  => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
    'autoload'     => [
        'psr-4' => [
            'Hoogi91\\Spreadsheets\\' => 'Classes',
        ],
    ],
];

