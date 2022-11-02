<?php
/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twipsi\Facades\App;
use Twipsi\Support\Str;

/**
 * Define the "asset" function helper in global scope.
 */
if (!function_exists("asset")) {
    /**
     * Function to return the assets' path.
     *
     * We will try to find it in public compiled assets,
     * otherwise return the resource.
     * @throws Exception
     */
    function asset(string $extension): string
    {
        // Format extension.
        $ext = "/" . trim($extension, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        // Attempt to return the compiled asset.
        return Str::hay($asset)->remove(App::path("path.public"));
    }
}

/**
 * Define the "css" function helper in global scope.
 */
if (!function_exists("css")) {
    /**
     * Function to return the assets path of a css.
     *
     * We will try to find it in public compiled assets,
     * otherwise return the resource.
     * @throws Exception
     */
    function css(string $css): string
    {
        // Format extension.
        $ext = "/css/" . trim($css, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        // Attempt to return the compiled asset.
        return Str::hay($asset)->remove(App::path("path.public"));
    }
}

/**
 * Define the "js" function helper in global scope.
 */
if (!function_exists("js")) {
    /**
     * Function to return the assets path of a js.
     *
     * We will try to find it in public compiled assets,
     * otherwise return the resource.
     * @throws Exception
     */
    function js(string $js): string
    {
        // Format extension.
        $ext = "/js/" . trim($js, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        // Attempt to return the compiled asset.
        return Str::hay($asset)->remove(App::path("path.public"));
    }
}

/**
 * Define the "img" function helper in global scope.
 */
if (!function_exists("img")) {
    /**
     * Function to return the assets path of an image.
     * @throws Exception
     */
    function img(string $image): string
    {
        // Format extension.
        $ext = "/img/" . trim($image, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        // Attempt to return the compiled asset.
        return Str::hay($asset)->remove(App::path("path.public"));
    }
}

/**
 * Define the "media" function helper in global scope.
 */
if (!function_exists("media")) {
    /**
     * Function to return the assets path of a media.
     * @throws Exception
     */
    function media(string $image): string
    {
        // Format extension.
        $ext = "/media/" . trim($image, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        // Attempt to return the compiled asset.
        return Str::hay($asset)->remove(App::path("path.public"));
    }
}

/**
 * Define the "data" function helper in global scope.
 */
if (!function_exists("data")) {
    /**
     * Function to return the assets path of an image.
     * @throws Exception
     */
    function data(string $file): string
    {
        // Format extension.
        $ext = "/data/" . trim($file, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        // Attempt to return the compiled asset.
        return Str::hay($asset)->remove(App::path("path.public"));
    }
}

/**
 * Define the "svg" function helper in global scope.
 */
if (!function_exists("svg")) {
    /**
     * Function to return the svg icon content.
     * @throws Exception
     */
    function svg(string $file, $class = null): string
    {
        // Format extension.
        $ext = "/media/icons/" . trim($file, "/");

        // Check fi the required asset exists.
        if (!is_file($asset = App::path("path.assets") . $ext)) {
            throw new Exception("Resource not found [$asset]", 404);
        }

        $cls = array("svg-icon");

        if (!empty($class)) {
            $cls = array_merge($cls, explode(" ", $class));
        }

        $svg_content = file_get_contents($asset);

        $output = "<!--begin::Svg Icon | path: $file-->\n";
        $output .= "<span class=\"" . implode(" ", $cls) . "\">" . $svg_content . "</span>";
        $output .= "\n<!--end::Svg Icon-->";

        return $output;
    }
}
