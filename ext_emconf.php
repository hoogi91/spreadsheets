<?php
$EM_CONF[$_EXTKEY] = [
    'title'        => 'Spreadsheets',
    'description'  => 'Extension to add field definition and plugin to show and select informations from spreadsheets',
    'category'     => 'be',
    'author'       => 'Thorsten Hogenkamp',
    'author_email' => 'hoogi20@googlemail.com',
    'version'      => '1.1.1',
    'state'        => 'stable',
    'constraints'  => [
        'depends' => [
            'typo3' => '8.7.0-9.99.99',
        ],
    ],
    'autoload'     => [
        'psr-4' => [
            'Hoogi91\\Spreadsheets\\' => 'Classes',
        ],
    ],
];

