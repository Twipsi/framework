<?php

namespace Twipsi\Foundation\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Twipsi\Components\File\FileBag;
use Twipsi\Components\Router\RouteFactory;
use Twipsi\Foundation\Console\Command;

#[AsCommand(name: 'routes:list')]
class RouteListCommand extends Command
{
    /**
     * The name of the command.
     *
     * @var string
     */
    protected string $name = 'routes:list';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'List all the routes and conditions';

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle(): void
    {
        $repository = $this->discoverRoutes(
            $this->app->path('path.routes')
        );

        if(is_null($repository)) {
            $this->render->error(
                sprintf("Route directory [%s] could not be found", $this->app->path('path.routes'))
            );

            return;
        }

        $routes = $this->processRoutes($this->app->get('route.factory'));

        $this->table(['Url', 'Name', 'Method'], $routes);
    }

    /**
     * Discover routes.
     *
     * @param string $where
     * @return array|null
     */
    protected function discoverRoutes(string $where): ?array
    {
        return is_dir($where)
            ? (new FileBag($where, 'php'))->includeAll()
            : null;
    }

    /**
     * Process the registered routes.
     *
     * @param RouteFactory $factory
     * @return array
     */
    protected function processRoutes(RouteFactory $factory): array
    {
        foreach ($factory->routes() as $route => $object) {
            $routes[] = [
                $object->getUrl(),
                $route, implode(', ', array_keys($object->getAllowedRequestMethods()))
            ];
        }

        return $routes ?? [];
    }
}