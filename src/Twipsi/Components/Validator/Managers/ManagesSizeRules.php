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

trait ManagesSizeRules
{
    /**
     * Validate Minimum string length.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param string $option
     * 
     * @return bool
     */
    protected function validateMin(string $identifier, mixed $value, array $option): bool 
    {
       if($value instanceof UploadedFile){
            return $value->getSize() >= $option['min'];
       }

       if(is_array($value)) {
            return count($value)>= $option['min'];
       }

        return strlen($value) >= $option['min'];
    }

    /**
     * Validate Maximum string length.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param string $option
     * 
     * @return bool
     */
    protected function validateMax(string $identifier, mixed $value, ?array $option): bool 
    {
       if($value instanceof UploadedFile){
            return $value->getSize() <= $option['max'];
       }

       if(is_array($value)) {
            return count($value) <= $option['max'];
       }

        return strlen($value) <= $option['max'];
    }

    /**
     * Validate size of file or string.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param string $option
     * 
     * @return bool
     */
    protected function validateSize(string $identifier, mixed $value, ?array $option): bool 
    {
       if($value instanceof UploadedFile){
            return $value->getSize() === $option['size'];
       }

       if(is_array($value)) {
            return count($value) === $option['size'];
       }

       return strlen($value) === $option['size'];
    }

    /**
     * Validate size of file or string between range.
     * 
     * @param string $identifier
     * @param string|null $value
     * @param array|null $option
     * 
     * @return bool
     */
    protected function validateBetween(string $identifier, mixed $value, ?array $option): bool
    {
        if($value instanceof UploadedFile){
            return $value->getSize() >= $option['min'] && 
            $value->getSize() <= $option['max'];
        }

        if(is_array($value)) {
            return count($value) >= $option['min'] && 
            count($value) <= $option['max'];
        }

       return strlen($value) >= $option['min'] && 
       strlen($value) <= $option['max'];
    }
}