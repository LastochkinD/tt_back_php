<?php

return [
    'server' => '77.222.40.147', // FTP server hostname or IP
    'port' => 21,
    'username' => 'romablunt_ttback',
    'password' => 'RP4JKAY$3YP5QkNA',
    'remote_dir' => '/', // Remote directory to upload to (e.g., '/' or '/public_html')
    'use_whitelist' => true, // true to use inclusions instead of exclusions
    'local_inclusions' => [
        'models',
        'controllers',
        'config/web.php',
    ],
    'local_exclusions' => [
        '.git',
        '.gitignore',
        'tests',
        'runtime',
        'vagrant',
        '.github',
        'composer.json',
        'composer.lock',
        'Vagrantfile',
        'requirements.php',
        'technical-specification-yii2-port.md',
        'api-documentation.md',
        'codeception.yml',
        'LICENSE.md',
        'README.md',
        'yiic.php',
        'yii.bat',
        '.bowerrc',
        'views',
        'migrations',
        'filters',
        'mail',
        'widgets',
        'commands',
    ],
];
