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

namespace Twipsi\Components\User;

use Twipsi\Components\Database\DatabaseManager;
use Twipsi\Components\User\Interfaces\IUserProvider;
use Twipsi\Foundation\ConfigRegistry;
use Twipsi\Foundation\Exceptions\NotSupportedException;

final class UserProvider
{
    /**
     * Database connector.
     *
     * @var DatabaseManager
     */
    protected DatabaseManager $db;

    /**
     * The configuration.
     *
     * @var ConfigRegistry
     */
    protected ConfigRegistry $config;

    /**
     * User provider Constructor.
     *
     * @param DatabaseManager $db
     * @param ConfigRegistry $config
     */
    public function __construct(DatabaseManager $db, ConfigRegistry $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Create the User Provider based on configuration.
     *
     * @param string $provider
     * @return IUserProvider
     * @throws NotSupportedException
     */
    public function create(string $provider): IUserProvider
    {
        $config = $this->config->get("auth.providers." . $provider);
        $method = "create" . ucfirst($config->get("provider")) . "UserProvider";

        if (! method_exists($this, $method)) {

            throw new NotSupportedException(
                sprintf("The requested user provider [%s] is not supported", $config->get("provider"))
            );
        }

        return $this->{$method}($config);
    }

    /**
     * Create a database based user provider.
     *
     * @param ConfigRegistry $config
     * @return DatabaseUserProvider
     */
    public function createDatabaseUserProvider(ConfigRegistry $config): DatabaseUserProvider
    {
        $connection = $this->db->create($this->config->get("database.driver"));

        return new DatabaseUserProvider($connection, $config->get("table"));
    }

    /**
     * Create a model based user provider.
     *
     * @param ConfigRegistry $config
     * @return ModelUserProvider
     */
    public function createModelUserProvider(ConfigRegistry $config): ModelUserProvider
    {
        return new ModelUserProvider($config->get("model"));
    }
}
