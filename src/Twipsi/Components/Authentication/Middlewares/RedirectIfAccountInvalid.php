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

namespace Twipsi\Components\Authentication\Middlewares;

use Twipsi\Bridge\Auth\ValidatesAccounts;
use Twipsi\Components\Authentication\AuthenticationManager;
use Twipsi\Components\Authentication\Drivers\Interfaces\AuthDriverInterface;
use Twipsi\Components\Http\HttpRequest as Request;
use Twipsi\Components\Http\Response\Interfaces\ResponseInterface;
use Twipsi\Foundation\Middleware\MiddlewareInterface;

class RedirectIfAccountInvalid implements MiddlewareInterface
{
    use ValidatesAccounts;

    /**
     * The authentication driver.
     */
    protected AuthDriverInterface $driver;

    /**
     * Session middleware Constructor
     */
    public function __construct(protected AuthenticationManager $auth)
    {
        $this->driver = $this->auth->driver();
    }

    /**
     * Resolve middleware logics.
     */
    public function resolve(Request $request, ...$args): ResponseInterface|bool
    {
        return $this->validateAccount($request, $request->user());
    }

    /**
	 * Log the user out of the system.
	 * 
	 * @param Request $request
	 * 
	 * @return void
	 */
	public function logout(Request $request): void
	{
		$this->driver->logout(true);
		$request->session()->destroy();
		$request->session()->refresh();
	}
}
