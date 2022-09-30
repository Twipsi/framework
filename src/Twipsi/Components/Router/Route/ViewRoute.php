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

namespace Twipsi\Components\Router\Route;

use Throwable;
use Twipsi\Components\Http\Response\ViewResponse;
use Twipsi\Components\Router\Exceptions\InvalidRouteException;

final class ViewRoute extends Route
{
    /**
     * The view to find.
     *
     * @var string
     */
    protected string $view;

    /**
     * The data to pass to the view.
     *
     * @var array
     */
    protected array $data;

    /**
     * Redirect route constructor.
     *
     * @param string $uri
     * @param array $methods
     */
    public function __construct(string $uri, array $methods)
    {
        parent::__construct($uri, null, $methods);
    }

    /**
     * Initiate route rendering and return a valid response.
     *
     * @return ViewResponse
     */
    public function render(): ViewResponse
    {
        if (!$this->view) {
            throw new InvalidRouteException(sprintf("No view set for route %s", $this->getUrl()));
        }

        // Build the view through the application.
        try {
            return new ViewResponse($this->view, $this->data);

            // If there was an exception building the view then throw invalid route.
        } catch (Throwable $e) {
            throw new InvalidRouteException(sprintf(
                "The view '%s' does not exist.", $this->view
            ), $e);
        }
    }

    /**
     * Set the view that should be loaded.
     *
     * @param string $view
     * @return $this
     */
    public function view(string $view): ViewRoute
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set the data that should be passed to the view.
     *
     * @param array $data
     * @return ViewRoute
     */
    public function data(array $data): ViewRoute
    {
        $this->data = $data;

        return $this;
    }
}
