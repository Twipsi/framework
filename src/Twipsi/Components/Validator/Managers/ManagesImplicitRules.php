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

use Twipsi\Components\File\UploadedFile;

trait ManagesImplicitRules
{
    /**
     * Check if the identifier exists in the validatable data.
     * 
     * @param string $identifier
     * 
     * @return bool
     */
    protected function validatePresent(string $identifier): bool
    {
        return $this->data->has($identifier);
    }

    /**
     * Check When the identifier exists is not empty.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateFilled(string $identifier, mixed $value): bool
    {
        return $this->validatePresent($identifier) && ! empty($value);
    }

    /**
     * Check if the identifier exists and is not null or empty.
     * 
     * @param string $identifier
     * @param string|null $value
     * 
     * @return bool
     */
    protected function validateRequired(string $identifier, mixed $value): bool
    {
        if(is_null($value) || (is_string($value) && trim($value) === '')) {
            return false;
        }

        if(is_array($value) && empty($value)) {
            return false;
        }

        if($value instanceof UploadedFile && empty($value->getPath())) {
            return false;
        }

        return $this->validatePresent($identifier);
    }

    /**
     * Check if any of the identifiers exist before requiring
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateRequiredWith(string $identifier, mixed $value, array $option): bool
    {
        foreach($option['dependency'] as $field) {
            if(! $this->validatePresent($field)) {
                $results[] = $field;
            }
        }

        return empty(array_diff($option['dependency'], $results ?? [])) || 
            $this->validateRequired($identifier, $value);
    }

    /**
     * Check if all of the identifiers exist before requiring
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateRequiredWithAll(string $identifier, mixed $value, array $option): bool
    {
        foreach($option['dependency'] as $field) {
            if(! $this->validatePresent($field)) {
                return true;
            }
        }

        return $this->validateRequired($identifier, $value);
    }

    /**
     * Check if any of the identifiers dont exist before requiring.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateRequiredWithout(string $identifier, mixed $value, array $option): bool
    {
        foreach($option['dependency'] as $field) {
            if($this->validatePresent($field)) {
                return true;
            }
        }

        return $this->validateRequired($identifier, $value);
    }

    /**
     * Check if none of the identifiers exist before requiring.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateRequiredWithoutAll(string $identifier, mixed $value, array $option): bool
    {
        foreach($option['dependency'] as $field) {
            if($this->validatePresent($field)) {
                $results[] = $field;
            }
        }

        return ! empty($results) || $this->validateRequired($identifier, $value);
    }
    
    /**
     * Check if the identifier exists and is equal to the option.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateRequiredIf(string $identifier, mixed $value, array $option): bool
    {
        if(! $this->validateRequired($identifier, $value)) {
            return false;
        }

        foreach($option['dependency'] as $dependency) {
            if($value == $dependency) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the identifier exists and is not equal to the option.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array $option
     * 
     * @return bool
     */
    protected function validateRequiredUnless(string $identifier, mixed $value, array $option): bool
    {
        if(! $this->validateRequired($identifier, $value)) {
            return false;
        }

        foreach($option['dependency'] as $dependency) {
            if($value == $dependency) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any type of checkbox has been checked.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateAccepted(string $identifier, mixed $value): bool
    {
        return $this->validatePresent($identifier) && 
            ($value === 'yes' || $value === 'on' || $value === '1' || 
            $value === 'true' || $value === 1 || $value === true);
    }

    /**
     * Check if any type of checkbox has been unchecked.
     * 
     * @param string $identifier
     * @param mixed $value
     * 
     * @return bool
     */
    protected function validateDeclined(string $identifier, mixed $value): bool
    {
        return $this->validatePresent($identifier) && 
            ($value === 'no' || $value === 'off' || $value === '0' || 
            $value === 'false' || $value === 0 || $value === false);
    }
}