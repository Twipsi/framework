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

namespace Twipsi\Components\Cookie;

use Twipsi\Support\Bags\ArrayBag as Container;

class CookieBag extends Container
{
    /**
     * Queued Cookies.
     *
     * @var Container
     */
    public Container $queued;

    /**
     * Constructor
     *
     * @param array $cookies
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $name => $value) {
            // Put all the cookies in a cookie item.
            $cookie = new Cookie($name, $value);

            $cookieObjects[$name] = $cookie;
        }

        parent::__construct($cookieObjects ?? []);

        $this->queued = new Container();
    }

    /**
     * Queue cookie for late response.
     *
     * @param ...$parameters
     * @return void
     */
    public function queue(...$parameters): void
    {
        $this->queued->set($parameters[0], new Cookie(...$parameters));
    }

    /**
     * Make any cookie or queued cookie expire
     *
     * @param string $name
     * @return void
     */
    public function expire(string $name): void
    {
        // Remove it from the cookie bag also
        if ($this->has($name)) {
            $this->delete($name);
        }

        $this->queued->set($name, new Cookie($name, '', -2628000));
    }

    /**
     * Return a cookie in queue.
     *
     * @param string|null $name
     * @return Container|Cookie|null
     */
    public function getQueuedCookies(string $name = null): Container|Cookie|null
    {
        if (null !== $name) {
            return $this->queued->get($name);
        }

        return $this->queued;
    }
}
