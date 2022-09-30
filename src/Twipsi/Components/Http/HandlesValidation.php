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

use Closure;
use Twipsi\Components\Validator\ValidatorFactory;
use Twipsi\Components\Validator\Validator;
use Twipsi\Components\Validator\Exceptions\ValidatorException;

trait HandlesValidation
{
    /**
     * Validator factory object.
     *
     * @var Closure
     */
    protected Closure $validationFactory;

    /**
     * The current validator.
     * 
     * @var Validator
     */
    protected Validator $validator;

    /**
     * Set the validatorFactory to the request.
     *
     * @param Closure $loader
     *
     * @return void
     */
    public function setValidator(Closure $loader): void
    {
        $this->validationFactory = $loader;
    }

    /**
     * Validate request input based on rules an handle the errors.
     *
     * @param array $rules
     *
     * @return bool
     */
    public function validate(array $rules): bool
    {
        $this->validator = $this->loadFactory()->create($this->input()->all(), $rules);

        if(! $validated = $this->validator->validates()) {

            throw ValidatorException::with($this->validator->errors()->all());
        }

        return $validated;
    }

    /**
     * Return the current validator.
     * 
     * @return Validator
     */
    public function validator(): Validator
    {
        return $this->validator;
    }

    /**
     * Load the validator factory.
     * 
     * @return ValidatorFactory
     */
    protected function loadFactory(): ValidatorFactory
    {
        return call_user_func($this->validationFactory);
    }
}
