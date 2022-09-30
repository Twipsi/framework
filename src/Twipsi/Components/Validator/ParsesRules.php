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

trait ParsesRules
{
    /**
     * Parse the rule for options.
     * 
     * @param string $rule
     * 
     * @return array
     */
    protected function parseOptions(string $rule): array 
    {
        $parse = explode(':', $rule);

        if(!empty($parse[1])) {
            $options = $this->buildOptionNames($parse[0], explode(',', $parse[1]));
        }
        return [ array_shift($parse), !empty($options) ? $options : [] ];
    }

    /**
     * Parse the rules to match requirements.
     * 
     * @param string $rule
     * 
     * @return array
     */
    protected function parseRules(string $rule): array
    {
        $rules = explode('|', $rule);

        foreach($rules as $rule) {
            $pack[] = $this->parseRuleName($rule);
        }

        return $pack;
    }

    /**
     * Parse the rule name to fit requirements.
     * 
     * @param string $rule
     * 
     * @return string
     */
    protected function parseRuleName(string $rule): string
    {
        return ucfirst($rule);
    }

    /**
     * Attempt to name the options.
     * 
     * @param string $rule
     * @param array $options
     * 
     * @return array
     */
    protected function buildOptionNames(string $rule, array $options): array
    {
        // If its a size rule.
        if(in_array($rule, $this->sizeRules)) {
            if($rule === 'Between') {
                return ['min' => array_shift($options), 'max' => array_shift($options)];
            }

            return [mb_strtolower($rule) => array_shift($options)];
        }

        // If its a dependant rule.
        if(in_array($rule, $this->dependentRules)) {

            return ['dependency' => $options];
        }

        return [mb_strtolower($rule) => array_shift($options)];
    }
}