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

namespace Twipsi\Foundation\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Twipsi\Foundation\Application\Application;

class CommandLoader implements CommandLoaderInterface
{
    /**
     * Application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Commands that need DI.
     *
     * @var array
     */
    protected array $commandMap = [];

    /**
     * Construct Command loader.
     *
     * @param Application $app
     * @param array $commandMap
     */
    public function __construct(Application $app, array $commandMap)
    {
        $this->app = $app;
        $this->commandMap = $commandMap;
    }

    public function get(string $name): Command
    {
        // TODO: Implement get() method.
    }

    public function has(string $name): bool
    {
        // TODO: Implement has() method.
    }

    public function getNames(): array
    {
        // TODO: Implement getNames() method.
    }
}
