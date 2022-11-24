<?php return array (
  'component' => 
  array (
    'loaders' => 
    array (
      0 => 'Twipsi\\Foundation\\ComponentProviders\\CookieProvider',
      1 => 'Twipsi\\Foundation\\ComponentProviders\\SessionProvider',
    ),
    'aliases' => 
    array (
      'identifier.token' => 'Twipsi\\Tests\\Foundation\\Fakes\\ADriver',
    ),
  ),
  'security' => 
  array (
    'app_key' => 'XXXKEy',
    'encrypter' => 'aes-256-cbc',
    'keys' => 
    array (
      'secret_length' => 32,
      'csrf_length' => 64,
    ),
  ),
  'session' => 
  array (
    'name' => 'twipsi_session',
    'encrypt' => true,
    'driver' => 
    array (
      'driver' => 'file',
      'table' => 'tw_sessions',
    ),
  ),
  'system' => 
  array (
    'name' => 'twipsi_testing_context',
    'context' => 'testcase',
    'env' => 'testing',
    'timezone' => 'Africa/Johannesburg',
  ),
);