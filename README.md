# Twipsi Lightweight MVC SOLID PHP framework

## Under Development!!!

Details and documentation to come....
The loner framework that doesnt need any third party help...

## Index

### Autoload (No composure)

```php
spl_autoload_register( function ( $class )
{
  $root = dirname(__FILE__);
  $file = $root . '/' . str_replace('\\', '/', $class) . '.php';
  if( is_readable( $file ) ) {
    require $root . '/' . str_replace('\\', '/', $class) . '.php';
  }
});
```

### Index.php

```php
$app = new Twipsi\Foundation\Application\Application();
$app->route();
```

## The "Boot" folder

The "Boot" folder contains all client defined and system accessible
skeleton structure data. System wide Discovery services will read
these files to construct their classes

### Configuration
"Boot/Config/*.php" Files should be named appropriate to their section,
and return an array of data.

```php
// Subscribing... file: system.php
return [
  'maintenance' => false,
];

// Retrieving configuration

// Helper
config( 'system.maintenance' );
// Facade
Config::get( 'system.maintenance' );
```

### Events

### Middlewares

### Databases

## Application
## Routing
## Events
## Middlewares
## StateProviders
## Facades
## Helpers
