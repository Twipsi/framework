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

namespace Twipsi\Components\Http\Exceptions;

use RuntimeException;
use Twipsi\Components\Http\Response\Response;

class HttpResponseException extends RuntimeException
{
    /**
     * The response attached
     * 
     * @var Response
     */
    public Response $response;

    /**
     * Construct an Http Exception.
     * 
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the response.
     * 
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
