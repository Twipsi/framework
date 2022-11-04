<?php

return [
  'guest'          => \Twipsi\Components\Authentication\Middlewares\RedirectIfAuthenticated::class,
  'authenticated'  => \Twipsi\Components\Authentication\Middlewares\RedirectIfNotAuthenticated::class,
  'verified'       => \Twipsi\Components\Authentication\Middlewares\RedirectIfEmailNotVerified::class,
  'valid'          => \Twipsi\Components\Authentication\Middlewares\RedirectIfAccountInvalid::class,
  'argument'       => '\Twipsi\Components\Http\Middlewares\CrossOriginVerify@arg1,arg2,arg3',
];