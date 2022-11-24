<?php

return [
    'loaders' => [
        \Twipsi\Foundation\ComponentProviders\CookieProvider::class,
        \Twipsi\Foundation\ComponentProviders\SessionProvider::class,
    ],

    'aliases' => [
        'identifier.token' => \Twipsi\Tests\Foundation\Fakes\ADriver::class,
    ],
];
