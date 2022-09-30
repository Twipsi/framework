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

namespace Twipsi\Components\Router;

use Twipsi\Support\Str;

final class RouteGroup
{
    /**
     * Route group stack.
     *
     * @var array
     */
    protected array $stack = [];

    /**
     * Manage the stack in hierarchy.
     *
     * @param array $child
     * @return void
     */
    public function append(array $child): void
    {
        $child = $this->convertShortKeys($child);

        if (!$this->empty()) {
            $child = $this->mergeGroupAttributes($child);
        }

        $this->addToTheStack($child);
    }

    /**
     * Check if the stack is empty.
     *
     * @return bool
     */
    public function empty(): bool
    {
        return empty($this->stack);
    }

    /**
     * Return the lowest child in the stack.
     *
     * @return array|bool
     */
    public function last(): array|bool
    {
        return end($this->stack);
    }

    /**
     * Remove the lowest layer in the group stack.
     *
     * @return void
     */
    public function pop(): void
    {
        array_pop($this->stack);
    }

    /**
     * Add a new group to the stack.
     *
     * @param array $group
     * @return void
     */
    protected function addToTheStack(array $group): void
    {
        $this->stack[] = $group;
    }

    /**
     * Rename the short group keys to original one.
     *
     * @param array $options
     * @return array
     */
    protected function convertShortKeys(array $options): array
    {
        $renamed = array_map(fn($key) =>
        match ($key) {
            'n' => 'name',
            'ns' => 'namespace',
            'p' => 'prefix',
            'ct' => 'context',
            's' => 'scheme',
            'r'  => 'regex',
            'cd' => 'condition',
            'ex' => 'exception',
            'o'  => 'optional',
            'm' => 'middlewares',
            default => $key,
        }, array_keys($options));

        return array_combine($renamed, $options);
    }

    /**
     * Merge lower options with higher ones in the stack.
     *
     * @param array $child
     * @return array
     */
    protected function mergeGroupAttributes(array $child): array
    {
        $parent = $this->last();

        return array_merge($child, [
            'name' => $this->buildName($child, $parent),
            'namespace' => $this->buildNamespace($child, $parent),
            'prefix' => $this->buildPrefix($child, $parent),
            'context' => $this->buildContext($child, $parent),
            'scheme' => $this->buildScheme($child, $parent),
            'regex' => $this->buildRegex($child, $parent),
            'condition' => $this->buildCondition($child, $parent),
            'exception' => $this->buildException($child, $parent),
            'optional' => $this->buildOptional($child, $parent),
            'middlewares' => $this->buildMiddlewares($child, $parent),
        ]);
    }

    /**
     * Merge parent namespace with child's namespace.
     *
     * @param array $child
     * @param array $parent
     * @return string|null
     */
    protected function buildName(array $child, array $parent): ?string
    {
        $names = $this->filterAttributes($parent, $child, 'name');

        return array_reduce($names, function($carry, $name): string {

            $trimmed = trim($name, '.');
            return $carry . (!empty($carry) ? '.' . $trimmed : $trimmed);
        });
    }

    /**
     * Merge parent namespace with child's namespace.
     *
     * @param array $child
     * @param array $parent
     * @return string|null
     */
    protected function buildNamespace(array $child, array $parent): ?string
    {
        $namespaces = $this->filterAttributes($parent, $child, 'namespace');

        // If the child namespace starts with '\' then it should
        // not inherit the parent namespace.
        return array_reduce($namespaces, function($carry, $name): string {

            if (Str::hay($name)->first('\\')) {
                return trim($name, '\\');
            }

            $trimmed = trim($name, '\\');
            return $carry . (!empty($carry) ? '\\' . $trimmed : $trimmed);
        });
    }

    /**
     * Merge parent prefix with child's prefix.
     *
     * @param array $child
     * @param array $parent
     * @return string|null
     */
    protected function buildPrefix(array $child, array $parent): ?string
    {
        $prefixes = $this->filterAttributes($parent, $child, 'prefix');

        return array_reduce($prefixes, function($carry, $name): string {

            $trimmed = trim($name, '/');
            return $carry . (!empty($carry) ? '/' . $trimmed : $trimmed);
        });
    }

    /**
     * Merge parent prefix with child's context.
     *
     * @param array $child
     * @param array $parent
     * @return string|null
     */
    protected function buildContext(array $child, array $parent): ?string
    {
        $prefixes = $this->filterAttributes($parent, $child, 'context');

        return array_reduce($prefixes, function($carry, $name): string {

            $trimmed = trim($name, '/');
            return $carry . (!empty($carry) ? '/' . $trimmed : $trimmed);
        });
    }

    /**
     * Set the route scheme.
     *
     * @param array $child
     * @param array $parent
     * @return string|null
     */
    protected function buildScheme(array $child, array $parent): ?string
    {
        return $child['scheme'] ?? $parent['scheme'] ?? null;
    }

    /**
     * Merge parent regexes with child's conditions.
     *
     * @param array $child
     * @param array $parent
     * @return array
     */
    protected function buildRegex(array $child, array $parent): array
    {
        return array_merge($parent['regex'] ?? [], $child['regex'] ?? []);
    }

    /**
     * Merge parent conditions with child's conditions.
     *
     * @param array $child
     * @param array $parent
     * @return array
     */
    protected function buildCondition(array $child, array $parent): array
    {
        return array_merge($parent['condition'] ?? [], $child['condition'] ?? []);
    }

    /**
     * Merge parent exceptions with child's exceptions.
     *
     * @param array $child
     * @param array $parent
     * @return array
     */
    protected function buildException(array $child, array $parent): array
    {
        return array_merge($parent['exception'] ?? [], $child['exception'] ?? []);
    }

    /**
     * Merge parent optionals with child's exceptions.
     *
     * @param array $child
     * @param array $parent
     * @return array
     */
    protected function buildOptional(array $child, array $parent): array
    {
        return array_merge($parent['optional'] ?? [], $child['optional'] ?? []);
    }

    /**
     * Merge parent middlewares with child's middlewares.
     *
     * @param array $child
     * @param array $parent
     * @return array
     */
    protected function buildMiddlewares(array $child, array $parent): array
    {
        return array_merge($parent['middlewares'] ?? [], $child['middlewares'] ?? []);
    }

    /**
     * Filter out null type attributes.
     *
     * @param array $parent
     * @param array $child
     * @param string $name
     * @return array
     */
    protected function filterAttributes(array $parent, array $child, string $name): array
    {
        return array_filter([$parent[$name] ?? null, $child[$name] ?? null],
            fn($v): bool => !is_null($v)
        );
    }
}
