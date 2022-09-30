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

use RuntimeException;
use Twipsi\Components\File\UploadedFile;
use Twipsi\Components\Translator\Translator;

trait ManagesMessages
{
    /**
     * Get the error message for specific rules.
     * 
     * @param string $identifier
     * @param string $rule
     * @param array $parameters
     * 
     * @return string
     */
    protected function getMessage(string $identifier, string $rule, array $options): string 
    {
        // Check if we have any custom messages set and return it.
        if(! is_null($custom = $this->getCustomMessage($identifier, mb_strtolower($rule)))) {
            return $custom;
        }

        // If the rule is related to size validation.
        if(in_array($rule, $this->sizeRules)) {
            return $this->getSizeMessage($identifier, mb_strtolower($rule), $options);
        }

        // Else get the message from the translator component. 
        $key = 'validator.'.mb_strtolower($rule);

        if($value = $this->getTranslation($identifier, $key, $options)) {
            return $value;
        }

        return $key;
    }

    protected function getCustomMessage(string $identifier, string $rule): ?string 
    {
        $key = $identifier.'.'.$rule;

        if(! isset($this->customMessages[$key])) {
            return null;
        }

        return $this->customMessages[$key];
    }

    /**
     * Get error message for size related rules.
     * 
     * @param string $identifier
     * @param string $rule
     * 
     * @return string|null
     */
    protected function getSizeMessage(string $identifier, string $rule, array $options): string 
    {
        $type = $this->getIdentifierType($identifier);

        $key = 'validator.'.$rule.'.'.$type;

        return $this->getTranslation($identifier, $key, $options) ?? $key;
    }

    /**
     * Get the the type of data we are validating.
     * 
     * @param string $identifier
     * 
     * @return string
     */
    protected function getIdentifierType(string $identifier): string
    {
        if($this->hasRule($identifier, $this->numericRules)) {
            return 'numeric';
        }

        if($this->hasRule($identifier, ['Array'])) {
            return 'array';
        }

        if($this->getIdentifierValue($identifier) instanceof UploadedFile) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Attempt to get message from the translator.
     * 
     * @param string $key
     * 
     * @return [type]
     */
    protected function getTranslation(string $identifier, string $key, array $options):? string 
    {
        if(! $this->translator instanceof Translator) {
            throw new RuntimeException("Translator component is not set for the validator");
        }

        return $this->translator->get(
            $key, array_merge($options, ['identifier' => $identifier])
        );
    }
}