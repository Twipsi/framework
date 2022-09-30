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

use \DateTime;

trait ManagesTypeRules
{
    /**
     * Check if the value is a real string.
     * 
     * @param string $identifier
     * @param string|null $value
     * 
     * @return bool
     */
    protected function validateString(string $identifier, mixed $value): bool 
    {
        return is_string($value) && ! is_numeric($value);
    }

    /**
     * Check if string is numeric.
     * 
     * @param string $identifier
     * @param string|null $value
     * 
     * @return bool
     */
    protected function validateNumeric(string $identifier, mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Check if value is an integer.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateInteger(string $identifier, mixed $value): bool
    {
        return is_int($value);
    }

    /**
     * Check if value is an array.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateArray(string $identifier, mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Check if the value is bool.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateBool(string $identifier, mixed $value): bool
    {
        if($value === "0" || $value === "1" || $value === "true" || $value === "false") {
            return true;
        }

        return is_bool($value);
    }

    /**
     * Check if value is a real date.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateDate(string $identifier, mixed $value): bool
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false;
    }
}