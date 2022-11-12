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

use Twipsi\Foundation\Exceptions\ApplicationManagerException;
use Twipsi\Support\Bags\SimpleBag as Container;

class ImplantRegistry extends Container
{
    /**
     * Implant registry constructor.
     *
     * @param array $implants
     */
    public function __construct(array $implants = [])
    {
        parent::__construct($implants);
    }

    /**
     * Bind a parameter dependency to an abstract.
     *
     * @param string $abstract
     * @param array $parameters
     * @return void
     * @throws ApplicationManagerException
     */
    public function bind(string $abstract, array $parameters): void
    {
        // If there are no parameters.
        if (empty($parameters)) {
            throw new ApplicationManagerException(
                sprintf("No parameters provided to bind for abstract {%s}", $abstract)
            );
        }

        $this->set($abstract, $parameters);
    }
}
