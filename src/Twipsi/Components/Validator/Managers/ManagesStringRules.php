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

namespace Twipsi\Components\Validator\Managers;

trait ManagesStringRules
{
   /**
     * Check if there is atleast one uppercase character.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateUppercase(string $identifier, mixed $value): bool
    {
        return preg_match('/[A-Z]/', $value);
    }

    /**
     * Check if a string has any special characters.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateSpecial(string $identifier, mixed $value): bool
    {
        return preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $value) > 0;
    }

    /**
     * Check if its a valid email.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateEmail(string $identifier, mixed $value): bool
    {
        return ! empty( filter_var($value, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Simple phone number validation.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validatePhone(string $identifier, mixed $value): bool
    {
        $invalid = ['+', '-', '_', '.', '(', ')'];
        $filtered = filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        foreach($invalid as $character){
            $filtered = str_replace($character, '', $filtered);
        }

        return strlen($filtered) > 7 && strlen($filtered) < 14;
    }

    /**
     * Check if string is a valid url.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateUrl(string $identifier, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }
}