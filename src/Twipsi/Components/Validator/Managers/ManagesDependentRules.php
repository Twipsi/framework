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

use Twipsi\Support\Chronos;

trait ManagesDependentRules
{
    /**
     * Attempt to match value with another identifiers value.
     * 
     * @param string $identifier
     * @param mixed $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateMatch(string $identifier, mixed $value, array $option): bool
    {
       if($prev = $this->getIdentifierValue($option['dependency'][0])) {
            return $value === $prev; 
       }

       return false;
    }

    /**
     * Attempt to differenciate value with another identifiers value.
     * 
     * @param string $identifier
     * @param mixed $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateDifferent(string $identifier, mixed $value, array $option): bool
    {
       if($prev = $this->getIdentifierValue($option['dependency'][0])) {
            return $value !== $prev; 
       }

       return false;
    }

    /**
     * Check uniqueness in the databsase table provided.
     * 
     * @param string $identifier
     * @param mixed $value
     * @param array|null $option
     * 
     * @return bool
     */
    protected function validateUnique(string $identifier, mixed $value, ?array $option): bool
    {
        $db = $this->getDatabaseVerifier();

        $column = isset($option['dependency'][1]) ? $option['dependency'][1] : $identifier;

        return $db->count($option['dependency'][0], $column, $value, 
            isset($option['dependency'][2]) ? $option['dependency'][2] : null, 
            isset($option['dependency'][3]) ? $option['dependency'][3] : null, ) === 0;
    }

    /**
     * Check if identifier exists in the database.
     * 
     * @param mixed $identifier
     * @param mixed $value
     * @param array|null $option
     * 
     * @return bool
     */
    protected function validateExists($identifier, mixed $value, ?array $option): bool
    {
        $db = $this->getDatabaseVerifier();

        $column = isset($option['dependency'][1]) ? $option['dependency'][1] : $identifier;

        return $db->count($option[0], $column, $value, 
            isset($option['dependency'][2]) ? $option['dependency'][2] : null, 
            isset($option['dependency'][3]) ? $option['dependency'][3] : null, ) > 0;
    }

    /**
     * Check if the date is after another date.
     * 
     * @param mixed $identifier
     * @param mixed $value
     * @param array|null $option
     * 
     * @return bool
     */
    protected function validateAfter($identifier, mixed $value, ?array $option): bool
    {
        return Chronos::date($value)->travel($option['after']) <= 0;
    }

    /**
     * Check if the date is before another date.
     * 
     * @param mixed $identifier
     * @param mixed $value
     * @param array|null $option
     * 
     * @return bool
     */
    protected function validateBefore($identifier, mixed $value, ?array $option): bool
    {
        return Chronos::date($value)->travel($option['before']) > 0;
    }
}