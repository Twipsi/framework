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

namespace Twipsi\Foundation\ComponentProviders;

use Twipsi\Components\Database\DatabaseManager;
use Twipsi\Components\Model\Model;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;

class DatabaseProvider extends ComponentProvider implements DeferredComponentProvider
{
    /**
     * Register service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->keep('db.connector', function (Application $app) {
            return new DatabaseManager($app);
        });

        $this->app->keep('db.connection', function (Application $app) {
            $connection = $app->get('db.connector')->create();

            return $connection->setEventDispatcher($app->get('events'));
        });

        Model::setDBmanager($this->app->get('db.connector'));
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['db.connector', 'db.connection'];
    }
}
