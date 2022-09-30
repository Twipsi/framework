# Twipsi Event Component

## Dispatching Events

### Dispatching Methods

#### Dispatchable trait

To dispatch an event you can call the the ::dispatch static method
if the event uses the Dispatchable trait. The arguments passed to 
the method will be passed to the event constructor.

```php
Registered::dispatch($user)
```

Using the trait you can also dispatch an event conditionaly.

```php
Registered::dispatchif(false != true, $user)
Registered::dispatchElse(false != true, $user)
```

#### Facade

You can also dispatch an event directly from the facade class.

```php
Event::dispatch(new Registered($user));
```

#### Application 

You can call the event handler from the application container.

```php
$this->app->get('events')->dispatch(new Registered($user))
```

#### Helpers

You can use the globaly accessible event() function.

```php
event(new Registered($user))
```

### Dispatching Features

#### Dependency Resolving

You can also initialize the event class through the application DI container 
to resolve some needing component dependencies.
Dispatching while sending the class name and payload as an array will make 
the dispatcher resolve the class through the application IOC manager.

```php
...dispatch([Registered::class, $user, ...$args])
```

## Defining Listeners

Twipsi will scan the listeners directory and build the listener
collection accordingly. Every expected event defined in the resolve
method will be mapped to the listener.

```php
class Authentication implements ListenerInterface
{
    public function resolve(Login $event): void
    {
      // Do Stuff
    }
}
```
You can expect multiple events that will listen to the same method
in the same listener.

```php
class Authentication implements ListenerInterface
{
    public function resolve(Authenticated|Login $event): void
    {
      // Do Stuff
    }
}
```
In most cases you will want the same listener to listen to multiple
events but resolving them differently. 
So you can define custom methods 
that should start with the <b>'resolve'</b> letter.

```php
class Routing implements ListenerInterface
{
    public function resolveMatch(RouteMatchedEvent $event): void
    {
      // We have a match...
    }

    public function resolveNotFound(RouteNotFoundEvent $event): void
    {
      // Oops 404
    }
}
```
Or you can do the same but defining multiple events on each of them.
```php
class Authentication implements ListenerInterface
{
    public function resolveOK(Authenticated|Login $event): void
    {
      // Auth is good
    }
    
    public function resolveFailed(Unauthenticated|Logout $event): void
    {
      // Auth failed
    }
}
```