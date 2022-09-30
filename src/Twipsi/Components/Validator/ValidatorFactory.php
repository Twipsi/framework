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

namespace Twipsi\Components\Validator;

use Twipsi\Components\Validator\Validator;
use Twipsi\Components\Validator\DatabaseVerifier;
use Twipsi\Components\Translator\Translator;

class ValidatorFactory
{
    /**
     * Validator factory constructor
     */
    public function __construct(
        protected Translator $translator, 
        protected DatabaseVerifier $verifier) 
    {}

    /**
     * Create a new validator and set the dependencies.
     * 
     * @param array $data
     * @param array $rules
     * 
     * @return Validator
     */
    public function create(array $data, array $rules): Validator
    {
        $validator = new Validator($data, $rules);
        $validator->setDatabaseVerifier($this->verifier);
        $validator->setTranslator($this->translator);

        return $validator;
    }
}
