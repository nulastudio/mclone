<?php

return [
    // LOG
    'logFile' => LOG_PATH . '/' . date('Y-m-d') . '.log',

    /**
     * 是否允许safeClone
     * 
     * 请注意：safeClone时，未经认证的客户端将无法拉取mclone镜像仓库
     * 启用后客户端--safe --unsafe参数才会生效
     */
    'safeCloneEnable'  => false,
    /**
     * 启用safeClone后的默认值
     * 
     * 可被客户端--safe --unsafe参数覆盖
     */
    'safeCloneDefault' => false,
    /**
     * 开启后无视客户端--safe --unsafe参数，强制safeClone
     */
    'forceSafeClone'   => false,

    // DB
    'db'      => [
        'database_type' => 'sqlite',
        'database_file' => DATA_PATH . '/database.db',
        'prefix'        => 'mclone_',
    ],
];
