Twipsi Facade classes provide a simplified interface to the complex logic of one or
several subsystems. The Facade delegates the client requests to the
appropriate objects within the subsystem. The Facade is also responsible for
managing their lifecycle and scope. All of this shields the client from the undesired
complexity of the subsystem.

Usage:
------------

Facades can access complex instances statically without having to load dependencies,
initializing objects, handling instance objects, saving them into variables
and passing them to system specific components.

  like.
  ```php
    Event::listen( $listener );
  ```

  instead of.
  ```php
    $dispatcher = new Twipsi\Components\Events\Dispatcher();
    $dispatcher->listen( $listener );
    App::set( 'event', $dispatcher );
  ```

Chaining methods:
-----------------------

Facades should be accessed statically by default.

  ```php
  Event::listen( $listener );
  ```
The abstract facade methods return themselves and can be chained with the original class returns statically.

```php
Event::swap( new CustomDispatcher )::register( TestEventHolder:class, TestEventListener:class );
```

Or thanks to the __call magic method we can chain....

```php
Event::swap( new CustomDispatcher )->register( TestEventHolder:class, TestEventListener:class );
```

Managing instances:
---------------------

By default on application boot the framework passes the application instance
to the abstract facade to load global scope instances.

  ```php
  App::get( 'events' );
  ```

the facade accesses the initialized object from the application container.

  ```php
  Event::register( TestEventHolder:class, TestEventListener:class );
  ```

The initialized facade can be swapped with a custom instance and added
to the facade scope, but keeping the original instance in the application controller.

  ```php
  Event::swap( new CustomDispatcher )->register( TestEventHolder:class, TestEventListener:class );
  ```

The initialized facade can be reinitialized as new and added the the facade scope,
but keeping the original instance in the application controller.

  ```php
  Event::new()->register( TestEventHolder:class, TestEventListener:class );
  ```

The current object saved in the facade scope can be reset to the original one resting
in the application container.

  ```php
  Event::reset()->register( TestEventHolder:class, TestEventListener:class );
  ```

Practical use of facade instance manipulation:
----------------------
If you want to benefit from methods of a class in a temporary scope without
altering the global scope.

  ```php
  if( Event::new()->isDispatchable( TestEventListener:class ) ){

    $customDispatcher = Event::class();
    Log::save( 'events.register', $customDispatcher->getListener() );
    Event::reset()->register( TestEventHolder:class, TestEventListener:class );
  }
  ```
