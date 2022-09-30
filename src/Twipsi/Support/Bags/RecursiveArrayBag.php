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

namespace Twipsi\Support\Bags;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Twipsi\Support\Arr;

class RecursiveArrayBag extends ArrayBag
{
    /**
     * Contains parameters data.
     * 
     * @var array
     */
    protected array $parameters = [];

    /**
     * Construct our array based parameters.
     * 
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        foreach($parameters as $key => $parameter) {
            $this->set((string)$key, $parameter);
        }
    }

    /**
     * Return all the parameters excluding exceptions.
     * 
     * @param string ...$exceptions
     * 
     * @return array
     */
    public function all(string ...$exceptions): array
    {
        if (!func_get_args()) {
            return $this->parameters;
        }

        $sub = clone($this);

        foreach($exceptions as $exception) {
            $sub->delete($exception);
        }

        return $sub->all();
    }

    /**
     * Return all the parameters with the selection key in one array
     * 
     * @param string ...$keys
     * 
     * @return array
     */
    public function selected(string ...$keys): array
    {
        if (!func_get_args()) {
            return $this->parameters;
        }

        foreach($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results ?? [];
    }

    /**
     * Merge An array of paramters with current parameters.
     * 
     * @param array|ArrayBag $parameters
     * 
     * @return static
     */
    public function recursiveMerge(array|RecursiveArrayBag $parameters): static
    {
        if ($parameters instanceof ArrayBag) {
            $parameters = $parameters->all();
        }

        $this->parameters = array_merge_recursive($this->parameters, $parameters);

        return $this;
    }

    /**
     * Return all keys in the parameters.
     * 
     * @return array
     */
    public function keys(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->parameters), RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($iterator as $key => $value){
            $keys[] = $key;
        }

        return $keys ?? [];
    }

    /**
     * Set a parameter into parameters array recursivley.
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->parameters = Arr::hay($this->parameters)
            ->set($key, $value);

        return $this;
    }

    /**
     * Push a value into parameter with a key recursivley.
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function push(string $key, mixed $value): static
    {
        $this->parameters = Arr::hay($this->parameters)
        ->push($key, $value);

        return $this;
    }

    /**
     * Collect values out of an array based on a key recursively
     * keeping the parent key as the key.
     *
     * ex. ['key1' => ['key2' => ['container' => ['value' => 85552]]]].
     * output. ['container' => 85552].
     *
     * @param string $column
     * @return array
     */
    public function gather(string $column): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->parameters), RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($iterator as $key => $value){

            if($key === $column) {
                $p = $iterator->getSubIterator(($iterator->getDepth()-1))->key();
                $values[$p] = $value;
            }
        }

        return $values ?? [];
    }

    /**
     * Implode the gathered columns.
     *
     * @param string $column
     * @param string $separator
     * @return string
     */
    public function implode(string $column, string $separator): string
    {
        return implode($separator, $this->gather($column));
    }

    /**
     * Retrieve a parameter from parameters array recursively.
     * 
     * @param string $key
     * @param mixed|null $default
     * 
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::hay($this->parameters)
            ->get($key, $default);
    }

    /**
     * Return and remove an element based on a key.
     * 
     * @param string $key
     * @param mixed|null $default
     * 
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);

        return $value;
    }

    /**
     * Check if parameter exists in parameters array recursively.
     * 
     * @param string $pointer
     * 
     * @return bool
     */
    public function has(string $pointer): bool
    {
        $depth = explode('.', $pointer);
        $base = $this->parameters;

        for($i=0; $i < count($depth); $i++) {

            if(!isset($base[$depth[$i]])) {
                return false;
            }

            $base = $base[$depth[$i]];
        }

        return true;
    }

    /**
     * Find a value based on a endpoint key recursively.
     * 
     * @param string $pointer
     * 
     * @return mixed
     */
    public function find(string $pointer): mixed
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->parameters), RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($iterator as $key => $value){
            if($key === $pointer) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Search for a value and return the key.
     * 
     * @param string $pointer
     * @param string $sub
     * 
     * @return mixed
     */
    public function search(string $pointer, string $sub = null): mixed
    {
        $haystack = !is_null($sub) ? ($this->parameters[$sub] ?? []) 
                                    : $this->parameters;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($haystack), RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($iterator as $key => $value){
            if($value === $pointer) {

                if(($depth = $iterator->getDepth()-1) < 0) {
                    return $key;
                }
               
                while( $depth >= 0) {
                    $key = $iterator->getSubIterator($depth)->key().'.'.$key;
                    $depth--;
                }

                return !is_null($sub) ? $sub.'.'.$key : $key;
            }
        }

        return null;
    }

    /**
     * Check if value exists in parameters array.
     * 
     * @param string $value
     * 
     * @return bool
     */
    public function contains(string $value): bool
    {
        return !is_null($this->search($value));
    }

    /**
     * Remove a parameter from parameters array recursively.
     * 
     * @param string $key
     * 
     * @return static
     */
    public function delete(string $key): static
    {
        $this->parameters = Arr::hay($this->parameters)
            ->delete($key);

        return $this;
    }
}
