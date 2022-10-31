<?php

/*
* This file is part of the Twipsi package.
*
* (c) Petrik Gábor <twipsi@twipsi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Twipsi\Foundation;

use Closure;
use Twipsi\Components\File\Exceptions\FileNotFoundException;
use Twipsi\Components\File\FileItem;
use Twipsi\Support\Arr;
use Twipsi\Support\Str;

class DotEnv
{
    /**
     * The path to the .env file.
     * 
     * @var string
     */
    protected string $path;

    /**
     * The environment variables.
     * 
     * @var array<string,string>
     */
    protected array $variables = [];

    /**
     * Construct dotenv.
     *
     * @param string $path
     * @throws FileNotFoundException
     */
    public function __construct(string $path)
    {
        if(!file_exists($path)) {
            throw new FileNotFoundException(sprintf('%s does not exist', $path));
        }

        $this->path = $path;
    }

    /**
     * Load the .env file and register it.
     * 
     * @throws FileNotFoundException
     * @return void
     */
    public function load(): void
    {
        // Load the file
        $file = new FileItem($this->path);

        foreach($file->getlines() as $line){

            if($this->isCommented($line)) {
                continue;
            }

            // Parse the line and separate key and value.
            $env = $this->parseLine($line);

            // If it's a variadic value we will just put a closure
            // instead to access it after we have done parsing.
            if($this->IsVariadic($line)) {

                $env = [array_key_first($env) => fn($variables) 
                    => $variables[Str::hay(array_values($env)[0])->betweenFirst('"${', '}"')]
                ];
            }

            // Put all the variables in the collection besides comments and variadic.
            $this->variables = array_merge($this->variables, $env);
        }

        // Register the variables.
        $this->putEnv($this->variables);
    }

    /**
     * Check if the line is commented out.
     * 
     * @param string $line
     * 
     * @return bool
     */
    protected function isCommented(string $line): bool
    {
        return Str::hay(trim($line))->first('#');
    }

    /**
     * Check if a value is variadic.
     * 
     * @param string $line
     * 
     * @return bool
     */
    protected function IsVariadic(string $line): bool 
    {
        if('' === ($value = Str::hay(trim($line))->after('='))) {
            return false;
        }

        return Str::hay($value)->resembles('"${');
    }

    /**
     * Parse the line.
     * 
     * @param string $line
     * 
     * @return array<string,string>
     */
    protected function parseLine(string $line): array
    {
        return Arr::pair([$line], '=');
    }

    /**
     * Apply environment vars to ENV and SERVER.
     * 
     * @param array<string,string|Closure> $variables
     * 
     * @return void
     */
    protected function putEnv(array $variables): void
    {
        foreach($variables as $key => $value) {

            // If it already exists then continue;
            if(array_key_exists($key, $_SERVER) || array_key_exists($key, $_ENV)) {
                continue;
            }

            // If we have a variable as a value.
            if($value instanceof Closure) {
                $value = call_user_func($value, $variables);
            }

            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv(sprintf('%s=%s', $key, $value));
        }
    }
}