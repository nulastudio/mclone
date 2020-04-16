<?php

return [
    // LOG
    'logFile' => LOG_PATH . '/' . date('Y-m-d') . '.log',

    // DB
    'db'      => [
        'database_type' => 'sqlite',
        'database_file' => DATA_PATH . '/database.db',
        'prefix'        => 'mclone_',
    ],
];
