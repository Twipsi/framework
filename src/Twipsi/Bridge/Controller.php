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

namespace Twipsi\Bridge;

use Twipsi\Foundation\Application\Application;

abstract class Controller
{
    /**
     * Application container.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Abstract Controller constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
