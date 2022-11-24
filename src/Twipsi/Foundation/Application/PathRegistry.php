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

use Twipsi\Support\Bags\SimpleAccessibleBag as Container;

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
     *
     * @param string $basePath
     */
    public function __construct(string $basePath = "")
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        parent::__construct([]);
        $this->bindSystemPaths();
    }

    /**
     * Set the application paths.
     *
     * @return void
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
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ""): string
    {
        return $this->basePath . (!empty($path) ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Set the boot directory path.
     *
     * @param string $path
     * @return string
     */
    public function bootPath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);
        $location = $this->get('path.boot') ?? $base . "boot";

        return !empty($path)
            ? $location . $this->formatPath($path)
            : $location;
    }

    /**
     * Set the application directory path.
     *
     * @param string $path
     * @return string
     */
    public function applicationPath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "app" . $this->formatPath($path)
            : $base . "app";
    }

    /**
     * Set the configuration directory path.
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "config" . $this->formatPath($path)
            : $base . "config";
    }

    /**
     * Set the database directory path.
     */
    public function databasePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "database" . $this->formatPath($path)
            : $base . "database";
    }

    /**
     * Set the helpers directory path.
     *
     * @param string $path
     * @return string
     */
    public function helpersPath(string $path = ""): string
    {
        if (!empty($path)) {
            return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "Helpers" . $this->formatPath($path);
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "Helpers";
    }

    /**
     * Set the middleware directory path.
     *
     * @param string $path
     * @return string
     */
    public function middlewarePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "middleware" . $this->formatPath($path)
            : $base . "middleware";
    }

    /**
     * Set the public directory path.
     *
     * @param string $path
     * @return string
     */
    public function publicPath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "public" . $this->formatPath($path)
            : $base . "public";
    }

    /**
     * Set the resource directory path.
     *
     * @param string $path
     * @return string
     */
    public function resourcePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "resources" . $this->formatPath($path)
            : $base . "resources";
    }

    /**
     * Set the public assets directory path.
     *
     * @param string $path
     * @return string
     */
    public function assetsPath(string $path = ""): string
    {
        if(!empty($path)) {
            return $this->publicPath() . DIRECTORY_SEPARATOR . "assets/" . $this->formatPath($path);
        }
        return $this->publicPath() . DIRECTORY_SEPARATOR . "assets";
    }

    /**
     * Set the language directory path.
     *
     * @param string $path
     * @return string
     */
    public function languagePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "locale" . $this->formatPath($path)
            : $base . "locale";
    }

    /**
     * Set the route directory path.
     *
     * @param string $path
     * @return string
     */
    public function routePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "routes" . $this->formatPath($path)
            : $base . "routes";
    }

    /**
     * Set the storage directory path.
     *
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "storage" . $this->formatPath($path)
            : $base . "storage";
    }

    /**
     * Set the cache directory path.
     *
     * @param string $path
     * @return string
     */
    public function cachePath(string $path = ""): string
    {
        $base = $this->formatBasePath($this->basePath);

        return !empty($path)
            ? $base . "storage/cache" . $this->formatPath($path)
            : $base . "storage/cache";
    }

    /**
     * Format a provided path.
     *
     * @param string $path
     * @return string
     */
    protected function formatPath(string $path): string
    {
        return "/" . trim($path, "/");
    }

    /**
     * Format the base path.
     *
     * @param string $base
     * @return string
     */
    protected function formatBasePath(string $base): string
    {
        return !empty($base) ? $base . DIRECTORY_SEPARATOR : "";
    }
}
