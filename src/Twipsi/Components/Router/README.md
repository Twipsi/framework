# Twipsi Router Component

## Subscribing Routes

### Subscribing Methods

#### Facade

You can add routes directly from the facade class.

```php
Route::get('/', fn() => 'This is home');
```

#### Application

You can add routes from the application container.

```php
$this->app->get('route.factory')->get('/', fn() => 'This is home')
```

### Fluent Settings

#### Setting the route name

```php
...get('/', fn() => 'This is home')
    ->name('home')
```

#### Setting the namespace

Note: prepending a (\) in the fron means it will overrite
any parent namespaces from groups. otherrwise append it to the parent namespaces

```php
...get('/', fn() => 'This is home')
    ->namespace('\App\Controllers')
```

#### Setting the prefix

This will result in /admin/settings

```php
...get('/settings', fn() => 'This is settings')
    ->prefix('admin')
```

#### Setting the context

This will result in config/backend folder configuration files.

```php
...get('/settings', fn() => 'This is settings')
    ->prefix('admin')
    ->context('backend')
```

#### Setting default parameter values

We can pass default values to the callback while adding the route,
that will be passed to the callable when rendering the route.

```php
...get('/crm/admin/project/{page}', fn($page, $action) => 'This is the project module')
    ->default(['action' => 'view'])
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