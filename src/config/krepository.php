<?php
return [
    'path' => 'Repositories',
    'files' => [
        'model' => '{name}',
        'interface' => '{name}Repository',
        'data_mapper' => 'Db{name}Repository'
    ],
    'parent' => [
        // data mapper parent class configs
        'data_mapper' => [
            'config' => false,
            'class_name' => '',
            'namespace' => ''
        ],
        'model' => [
            'config' => false,
            'class_name' => '',
            'namespace' => ''
        ]
    ]
];