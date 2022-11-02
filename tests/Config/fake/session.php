<?php

return [
    'name' => 'twipsi_session',
    'encrypt' => true,
    'driver' => [
        'driver' => 'file',
        'table' => 'tw_sessions',
    ]
];
