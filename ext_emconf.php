<?php
$EM_CONF[$_EXTKEY] = [
    'title'        => 'Spreadsheets',
    'description'  => 'Extension to add field definition and plugin to show and select informations from spreadsheets',
    'category'     => 'be',
    'author'       => 'Thorsten Hogenkamp',
    'author_email' => 'hoogi20@googlemail.com',
    'version'      => '2.0.0',
    'state'        => 'stable',
    'constraints'  => [
        'depends' => [
            'typo3' => '9.5.0-10.9.99',
        ],
    ],
    'autoload'     => [
        'psr-4' => [
            'Hoogi91\\Spreadsheets\\' => 'Classes',
        ],
    ],
];

