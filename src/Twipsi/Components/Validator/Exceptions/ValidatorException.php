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

namespace Twipsi\Components\Validator\Exceptions;

use Twipsi\Facades\Validator as Factory;
use Twipsi\Components\Validator\Validator;

final class ValidatorException extends \Exception
{
    /**
     * The response status;
     * 
     * @var int
     */
    public int $status = 422;

    /**
     * THe url to redirect to.
     * 
     * @var null|string
     */
    public ?string $redirectUrl = null;

    /**
     * Create validator exception.
     * 
     * @param  protected Validator $validator
     */
    public function __construct(protected Validator $validator)
    {
        parent::__construct(self::toString($validator));
    }

    /**
     * Throw the exception with custom errors.
     * 
     * @param array $messages
     * 
     * @return ValidatorException
     */
    public static function with(array $messages): ValidatorException
    {
        $validator = Factory::create([], []);
        $validator->errors()->inject($messages);

        return new static($validator);
    }

    /**
     * Get the validator errors;
     * 
     * @return array
     */
    public function errors(): array 
    {
        return $this->validator->errors()->all();
    }

    /**
     * Set the status code;
     * 
     * @param int $code
     * 
     * @return ValidatorException
     */
    public function status(int $code): ValidatorException
    {
        $this->status = $code;

        return $this;
    }

    /**
     * Set the redirect url.
     * 
     * @param string $url
     * 
     * @return ValidatorException
     */
    public function redirect(string $url): ValidatorException
    {
        $this->redirectUrl = $url;

        return $this;
    }

    /**
     * Commpile exception data into string.
     * 
     * @return string
     */
    protected function toString(): string
    {
        if(empty($messages = $this->errors())) {
            return 'Some unspecified validation problem occured.';
        }

        return implode(' and ', $messages).'.';
    }
}


