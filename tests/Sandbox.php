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

namespace Twipsi\Tests;

use PHPUnit\Framework\TestCase;
use Twipsi\Foundation\Application\Application;

class Sandbox extends TestCase
{
    /**
     * Application instance.
     *
     * @var Application|null
     */
    protected Application|null $app;

    /**
     * Creates an application instance.
     *
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->app = $this->createApplication();
    }

    /**
     * Create the application environment.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../boot/app.php';

        $app->make('kernel')
            ->bootstrapSystem()
            ->bootstrapComponents();

        return $app;
    }
}
