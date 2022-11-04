<?php

return [
  'web' => [
      \Twipsi\Components\Http\Middlewares\StopForMaintenance::class,
  ],

  'app' => [
    \Twipsi\Components\Http\Middlewares\StopForMaintenance::class,
    \Twipsi\Components\Cookie\Middlewares\EncryptCookies::class,
    \Twipsi\Components\Cookie\Middlewares\AppendQueuedCookiesToResponse::class,
    \Twipsi\Components\Session\Middlewares\StartSession::class,
  ],
];