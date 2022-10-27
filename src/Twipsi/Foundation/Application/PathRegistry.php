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

namespace Twipsi\Foundation\Application;

use Twipsi\Support\Bags\ArrayAccessibleBag as Container;

class PathRegistry extends Container
{
    /**
     * The base root path.
     *
     * @var string
     */
    protected string $basePath = '';

    /**
     * Path registry constructor.
     */
    public function __construct(string $basePath = "")
    {
        $this->basePath = rtrim($basePath, 'DIRECTORY_SEPARATOR');

        $this->bindSystemPaths();
    }

    /**
     * Set the application paths.
     */
    protected function bindSystemPaths(): void
    {
        $this->set("path.base", $this->basePath());
        $this->set("path.environment", $this->basePath());
        $this->set("path.boot", $this->bootPath());
        $this->set("path.app", $this->applicationPath());
        $this->set("path.config", $this->configPath());
        $this->set("path.database", $this->databasePath());
        $this->set("path.helpers", $this->helpersPath());
        $this->set("path.middlewares", $this->middlewarePath());
        $this->set("path.locale", $this->languagePath());
        $this->set("path.public", $this->publicPath());
        $this->set("path.resources", $this->resourcePath());
        $this->set("path.assets", $this->assetsPath());
        $this->set("path.routes", $this->routePath());
        $this->set("path.storage", $this->storagePath());
        $this->set("path.cache", $this->cachePath());
    }

    /**
     * Set current system scope path.
     */
    public function basePath(string $path = ""): string
    {
        return $this->basePath . (!empty($path) ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Set the boot directory path.
     */
    public function bootPath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "boot" . $this->formatPath($path);
        }

        return $base . "boot";
    }

    /**
     * Set the application directory path.
     */
    public function applicationPath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "app" . $this->formatPath($path);
        }

        return $base . "app";
    }

    /**
     * Set the configuration directory path.
     */
    public function configPath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "config" . $this->formatPath($path);
        }

        return $base . "config";
    }

    /**
     * Set the database directory path.
     */
    public function databasePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "database" . $this->formatPath($path);
        }

        return $base . "database";
    }

    /**
     * Set the helpers directory path.
     */
    public function helpersPath(string $path = ""): string
    {
        if (!empty($path)) {
            return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "Helpers" . $this->formatPath($path);
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "Helpers";
    }

    /**
     * Set the middleware directory path
     */
    public function middlewarePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "middleware" . $this->formatPath($path);
        }

        return $base . "middleware";
    }

    /**
     * Set the public directory path.
     */
    public function publicPath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "public" . $this->formatPath($path);
        }

        return $base . "public";
    }

    /**
     * Set the resource directory path.
     */
    public function resourcePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "resources" . $this->formatPath($path);
        }

        return $base . "resources";
    }

    /**
     * Set the public assets directory path.
     */
    public function assetsPath(string $path = ""): string
    {
        return $this->publicPath() . DIRECTORY_SEPARATOR . "assets";
    }

    /**
     * Set the language directory path.
     */
    public function languagePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "locale" . $this->formatPath($path);
        }

        return $base . "locale";
    }

    /**
     * Set the route directory path.
     */
    public function routePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "routes" . $this->formatPath($path);
        }

        return $base . "routes";
    }

    /**
     * Set the storage directory path.
     */
    public function storagePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "storage" . $this->formatPath($path);
        }

        return $base . "storage";
    }

    /**
     * Set the cache directory path.
     */
    public function cachePath(string $path = ""): string
    {
        $base = !empty($this->basePath)
            ? $this->basePath . DIRECTORY_SEPARATOR
            : "";

        if (!empty($path)) {
            return $base . "storage/cache" . $this->formatPath($path);
        }

        return $base . "storage/cache";
    }

    protected function formatPath(string $path): string
    {
        return "/" . trim($path, "/");
    }
}
