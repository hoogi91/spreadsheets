<?php
$EM_CONF[$_EXTKEY] = [
    'title'        => 'Spreadsheets',
    'description'  => 'Extension to add field definition and plugin to show and select information from spreadsheets',
    'category'     => 'be',
    'author'       => 'Thorsten Hogenkamp',
    'author_email' => 'thorsten@hogenkamp-bocholt.de',
    'version'      => '3.3.0',
    'state'        => 'stable',
    'constraints'  => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
        ],
    ],
    'autoload'     => [
        'psr-4' => [
            'Hoogi91\\Spreadsheets\\' => 'Classes',
        ],
    ],
];

