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

use Twipsi\Components\Http\Exceptions\NotSupportedException;
use Twipsi\Components\Http\RequestFactory;

final class MemoryTest extends Sandbox
{
    public function testMemoryLeak(): void
    {
        $this->app->flush();
        $this->app = null;

        for ($i = 1; $i < 1000; ++$i) {
            $this->createApplication()->flush();

            dump('Using ' . ((int) (memory_get_usage(true) / (1024 * 1024))) . 'MB in ' . $i . ' iterations.');
        }

        $this->app = $this->createApplication();
    }
}
