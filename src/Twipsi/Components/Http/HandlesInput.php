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

namespace Twipsi\Components\Http;

use Twipsi\Components\Http\HttpRequest;
use Twipsi\Components\Http\InputBag;
use Twipsi\Components\File\UploadedFileBag;
use Twipsi\Support\Str;

trait HandlesInput
{
    /**
     * $_POST data.
     *
     * @var InputBag
     */
    public InputBag $request;

    /**
     * $_GET data.
     *
     * @var InputBag
     */
    public InputBag $query;

    /**
     * JSON data.
     *
     * @var InputBag
     */
    public InputBag $json;

    /**
     * $_FILES data.
     *
     * @var UploadedFileBag
     */
    public UploadedFileBag $files;

    /**
     * Set $_POST data.
     *
     * @param Httprequest $request
     * @param array $post
     *
     * @return void
     */
    public function setRequestData(Httprequest $request, array $post): void
    {
        $this->request = new InputBag($request, $post);
    }

    /**
     * Set $_GET data.
     *
     * @param Httprequest $request
     * @param array $get
     *
     * @return void
     */
    public function setQueryData(Httprequest $request, array $get): void
    {
        $this->query = new InputBag($request, $get);
    }

    /**
     * Set $_FILES data.
     *
     * @param array $files
     *
     * @return void
     */
    public function setFileData(array $files): void
    {
        $this->files = new UploadedFileBag($files);
    }

    /**
     * Return a data container based on parameter.
     *
     * @param string $method
     *
     * @return mixed
     */
    public function getData(string $method): mixed
    {
        switch ($method) {
            case "GET":
                return $this->request;
            case "POST":
                return $this->query;
            case "FILES":
                return $this->files;
        }

        return null;
    }

    /**
     * Return request input source.
     *
     * @return InputBag
     */
    public function getInputSource(): InputBag
    {
        if ($this->isJson()) {
            return $this->json;
        }

        return \in_array($this->getMethod(), ['GET', 'HEAD'])
            ? $this->query
            : $this->request;
    }

    /**
     * Get the current inputs, all or filtered.
     *
     * @param string|array $key
     * @return mixed
     */
    public function input(string|array $key = null): mixed
    {
        if(is_null($key)) {
            return $this->getInputSource();
        }

        if(is_array($key)) {
            return $this->getInputSource()->selected(...$key);
        }

        return $this->getInputSource()->get($key);
    }

    /**
     * Return an input value as a boolean.
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function bool(string $key): bool 
    {
        $value = $this->getInputSource()->get($key);

        if(in_array($value, ['1', 'on', 'yes', 'true', true, 1])) {
            return true;
        }

        return false;
    }

    /**
     * Check if request is Json.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->headers->has("Content-Type") &&
            Str::hay($this->headers->get("Content-Type")[0])->resembles(
                "json",
                "/json",
                "+json"
            );
    }
}
