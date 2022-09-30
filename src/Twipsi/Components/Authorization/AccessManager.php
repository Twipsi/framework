<?php
declare(strict_types=1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Components\Authorization;

use Closure;
use ReflectionFunction;
use InvalidArgumentException;
use Twipsi\Components\Authorization\Events\AuthorizationProcessedEvent;
use Twipsi\Components\Authorization\Exceptions\AuthorizationException;
use Twipsi\Components\User\Interfaces\IAuthenticatable as Authable;
use Twipsi\Facades\Event;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Applicables\App;
use Twipsi\Support\Arr;
use Twipsi\Support\Bags\ObjectBag;
use Twipsi\Support\Bags\RecursiveArrayBag as Container;
use Twipsi\Support\Str;

class AccessManager
{
    use App;

    /**
     * Construct action provider.
     * var userLoader -> The closure to retrieve the auth user.
     * var rules -> The rules assigned to actions.
     * var beforeCallbacks -> array containing before authorization callbacks.
     * var afterCallbacks -> array containing after authorization callbacks.
     */
    public function __construct(
        protected Closure $userLoader,
        protected Container $rules = new Container(),
        protected array $beforeCallbacks = [],
        protected array $afterCallbacks = []
    ) {
    }

    /**
     * Create a rule for a specific action.
     */
    public function create(
        string $action,
        callable|array|string $callable
    ): AccessManager {
        // If we have a callable of type [ProfilePolicy::class, 'view'].
        if (is_array($callable) && count($callable) === 2) {
            $callable = $callable[0] . "@" . $callable[1];
        }

        // If the callback is callable already jsut save it.
        if (is_callable($callable)) {
            $this->rules->set($action, $callable);

            // If the callback is a typehint (class@method)
        } elseif (is_string($callable) && Str::hay($callable)->contains("@")) {
            $this->rules->set(
                $action,
                $this->createCallback($action, $callable)
            );
        } else {
            throw new InvalidArgumentException(
                "The provided authorization callback cannot be processed."
            );
        }

        return $this;
    }

    /**
     * Build a valid callback from the provided callback string.
     */
    protected function createCallback(string $action, string $callback): Closure
    {
        [$class, $method] = explode("@", $callback);

        return function (mixed ...$args) use ($action, $class, $method) {
            $policy = $this->getPolicy($class);

            if (
                method_exists($policy, "before") &&
                $this->userCanResolveAuthorization(
                    $args[0],
                    get_class($policy),
                    "before"
                )
            ) {
                if (
                    !is_null(
                        $result = $policy->before($args[0], $action, $args)
                    )
                ) {
                    return $result;
                }
            }

            return $this->userCanResolveAuthorization(
                $args[0],
                get_class($policy),
                $method
            )
                ? $policy->{$method}(...$args)
                : false;
        };
    }

    /**
     * Check if a user has specific action(s).
     */
    public function has(string|array $actions): bool
    {
        return !empty($this->rules->selected($actions));
    }

    /**
     * Check if a action should be authorized.
     */
    public function allows(string $action, mixed ...$arguments): bool
    {
        return $this->check($action, ...$arguments);
    }

    /**
     * Check if a action should be denied.
     */
    public function denies(string $action, mixed ...$arguments): bool
    {
        return !$this->allows($action, ...$arguments);
    }

    /**
     * Check if a any of the listed actions are authorized.
     */
    public function any(string|array $actions, mixed ...$arguments): bool
    {
        // Run through the list and check if any of the abilities are authorizable.
        $authorized = array_filter($actions, function ($action) use (
            $arguments
        ) {
            return $this->check($action, ...$arguments);
        });

        return Arr::hay($actions)->searchAny($authorized);
    }

    /**
     * Check if none of the listed actions are authorized.
     */
    public function none(string|array $actions, mixed ...$arguments): bool
    {
        return !$this->any($actions, ...$arguments);
    }

    /**
     * Check if all of the listed actions are authorized.
     */
    public function check(string|array $actions, mixed ...$arguments): bool
    {
        // Run through the list and check if all the abilities are authorizable.
        $checked = array_filter($actions, function ($action) use (
            $arguments
        ) {
            return $this->process($action, ...$arguments);
        });

        return Arr::hay($checked)->search($actions);
    }

    /**
     * Process the action authorization and return exception if failed.
     */
    public function authorize(string $action, mixed ...$arguments): bool
    {
        if ($result = $this->process($action, ...$arguments)) {
            return $result;
        }

        $action = Str::hay($action)->has(".")
            ? Str::hay($action)->after(".")
            : $action;
        $where =
            isset($arguments[0]) && is_string($arguments[0])
                ? $arguments[0]
                : "global";

        throw new AuthorizationException($action, $where);
    }

    /**
     * Process the action authorization.
     */
    public function process(string $action, mixed ...$arguments): bool
    {
        $user = $this->loadUser();

        // Call any before callback set before processing any action
        // to be able to bypass the original actions (ex. if admin allow all).
        if (
            is_null(
                $result = $this->processBeforeCallbacks(
                    $user,
                    $action,
                    ...$arguments
                )
            )
        ) {
            $result = $this->processAuthorization(
                $user,
                $action,
                ...$arguments
            );
        }

        // Call any after callbacks set after processing any action.
        $result = $this->processAfterCallbacks(
            $user,
            $action,
            $result,
            ...$arguments
        );

        // Dispatch Authorization Processed event.
        Event::dispatch(AuthorizationProcessedEvent::class, $user, $action, $result);

        return $result;
    }

    /**
     * Process all the provided before callbacks.
     */
    protected function processBeforeCallbacks(
        ?Authable $user,
        string $action,
        mixed ...$arguments
    ): ?bool {
        foreach ($this->beforeCallbacks as $callback) {
            if (!$this->userCanResolveAuthorization($user, $callback)) {
                continue;
            }

            if (!is_null($result = $callback($user, $action, ...$arguments))) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Process all the provided after callbacks.
     */
    protected function processAfterCallbacks(
        ?Authable $user,
        string $action,
        bool $result,
        mixed ...$arguments
    ): bool {
        foreach ($this->afterCallbacks as $callback) {
            if (!$this->userCanResolveAuthorization($user, $callback)) {
                continue;
            }

            $response = $callback($user, $action, $result, ...$arguments);
        }

        return $response ?? $result;
    }

    /**
     * Process authorization for the requested action.
     */
    protected function processAuthorization(
        ?Authable $user,
        string $action,
        mixed ...$arguments
    ): bool {
        if (
            isset($arguments[0]) &&
            ($policy = $this->getPolicy($arguments[0])) instanceof Policy &&
            ($callback = $this->resolvePolicy($action, $policy))
        ) {
            array_shift($arguments);
            return $callback($user, ...$arguments);
        }

        return $this->rules->has($action) &&
            $this->userCanResolveAuthorization(
                $user,
                $this->rules->get($action)
            )
            ? $this->rules->get($action)($user, ...$arguments)
            : false;
    }

    /**
     * Attempt to resolve the policy based on actions.
     */
    protected function resolvePolicy(
        string $action,
        Policy $policy
    ): bool|Closure {
        $action = Str::hay($action)->has(".")
            ? Str::hay($action)->after(".")
            : $action;

        if (!method_exists($policy, $action)) {
            return false;
        }

        return function (?Authable $user, mixed ...$arguments) use (
            $action,
            $policy
        ) {
            if (
                $this->userCanResolveAuthorization(
                    $user,
                    get_class($policy),
                    "before"
                )
            ) {
                // Call the policies registered before method.
                if (
                    !is_null(
                        $result = $policy->before($user, $action, ...$arguments)
                    )
                ) {
                    return $result;
                }
            }

            return $this->userCanResolveAuthorization(
                $user,
                get_class($policy),
                $action
            )
                ? $policy->{$action}($user, ...$arguments)
                : null;
        };
    }

    /**
     * Check if the current user can resolve authorization method.
     */
    protected function userCanResolveAuthorization(
        ?Authable $user,
        callable|string|array $callable,
        string $method = null
    ): bool {
        if (!is_null($user)) {
            return true;
        }

        if ($callable instanceof Closure) {
            return $this->closureAllowsGuests($callable);
        }

        if (!is_null($method)) {
            return $this->actionAllowsGuests($callable, $method);
        }

        if (is_array($callable) && isset($callable[1])) {
            return $this->actionAllowsGuests($callable[0], $callable[1]);
        }

        return false;
    }

    /**
     * Check if a policy action allows guests.
     * Action methods first parameter should be nullable.
     */
    protected function actionAllowsGuests(
        string $class,
        string $method = null
    ): bool {
        try {
            $policy = new ObjectBag($class);
            $method = $policy->getMethod($method);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        if (!($parameter = $method?->getParameters()[0])) {
            return false;
        }

        return $parameter->hasType() && $parameter->allowsNull();
    }

    /**
     * Check if a policy closure allows guests.
     * Closures first parameter should be nullable.
     */
    protected function closureAllowsGuests(Closure $closure): bool
    {
        try {
            $policy = new ReflectionFunction($closure);
            $parameter = $policy->getParameters()[0];
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return $parameter->hasType() && $parameter->allowsNull();
    }

    /**
     * Get policy object for a policy class.
     */
    public function getPolicy(string $class): ?Policy
    {
        if (!class_exists($class)) {
            if (Str::hay($class)->has("\\")) {
                $class = "\\App\Policies\\" . Str::hay($class)->afterLast("\\");
            } else {
                $class = "\\App\Policies\\" . trim($class, "\\");
            }
        }

        try {
            return $this->app->make($class);
        } catch (ApplicationManagerException $e) {
            return null;
        }
    }

    /**
     * Set a callback to be executed before authorization.
     */
    public function before(Closure $closure): void
    {
        $this->beforeCallbacks[] = $closure;
    }

    /**
     * Set a callback to be executed after authorization.
     */
    public function after(Closure $closure): void
    {
        $this->afterCallbacks[] = $closure;
    }

    /**
     * Return the container with all the rules.
     */
    public function rules(): Container
    {
        return $this->rules;
    }

    /**
     * Add user to user loader.
     */
    public function user(Authable $user): void
    {
        $this->userLoader = function () use ($user) {
            return $user;
        };
    }

    /**
     * Load the requested user.
     */
    public function loadUser(): ?Authable
    {
        return call_user_func($this->userLoader);
    }
}
