<?php
use Cake\Core\Configure;

return [
    'Swagger' => [
        'ui' => [
            'title' => 'Cakephp-base',
            'validator' => true,
            'api_selector' => true,
            'route' => '/swagger/',
            'schemes' => ['http', 'https']
        ],
        'docs' => [
            'crawl' => Configure::read('debug'),
            'route' => '/swagger/docs/',
            'cors' => [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST',
                'Access-Control-Allow-Headers' => 'X-Requested-With'
            ]
        ],
        'library' => [
            'api' => [
                'include' => ROOT . DS . 'src',
                
            ],
        ]
    ]
];