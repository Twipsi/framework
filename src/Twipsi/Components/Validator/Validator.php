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

use \RuntimeException;
use Twipsi\Foundation\Exceptions\NotSupportedException;
use Twipsi\Support\Bags\ArrayBag as Container;
use Twipsi\Components\Mailer\MessageBag;
use Twipsi\Components\File\UploadedFile;
use Twipsi\Components\Validator\Managers\ManagesMessages;
use Twipsi\Components\Validator\Managers\ManagesImplicitRules;
use Twipsi\Components\Validator\Managers\ManagesDependentRules;
use Twipsi\Components\Validator\Managers\ManagesTypeRules;
use Twipsi\Components\Validator\Managers\ManagesStringRules;
use Twipsi\Components\Validator\Managers\ManagesSizeRules;
use Twipsi\Components\Translator\Translator;
use Twipsi\Support\Arr;

class Validator
{
    use ManagesMessages, 
        ManagesImplicitRules,
        ManagesDependentRules,
        ManagesTypeRules,
        ManagesStringRules,
        ManagesSizeRules,
        ParsesRules;

    /**
     * The container storing data that should be validated.
     * 
     * @var Container
     */
    protected Container $data;

    /**
     * The messages container.
     * 
     * @var MessageBag
     */
    protected MessageBag $messages;

    /**
     * The custom message container.
     * 
     * @var array
     */
    protected array $customMessages = [];

    /**
     * Excluded identifiers.
     * 
     * @var array
     */
    protected array $excluded = [];

    /**
     * The container holding validated identifiers.
     * 
     * @var array
     */
    protected array $validated = [];

    /**
     * The container holding failed identifiers.
     * @var array
     */
    protected array $failed = [];

    /**
     * Container for the set rules.
     * 
     * @var array
     */
    protected array $rules = [];

    /**
     * Wether we should stop on first fail.
     * 
     * @var bool
     */
    protected bool $stopOnFail = false;

    /**
     * Status of validation.
     * 
     * @var bool
     */
    protected bool $validationProcessed;

    /**
     * The database verifier object.
     * 
     * @var DatabaseVerifier
     */
    protected DatabaseVerifier $verifier;

    /**
     * The translator object.
     * 
     * @var Translator
     */
    protected Translator $translator;

    /**
     * Rules related to size.
     * 
     * @var array
     */
    protected array $sizeRules = [
        'Size', 
        'Between', 
        'Min', 
        'Max',
    ];

    /**
     * Rules related to numeric.
     * 
     * @var array
     */
    protected array $numericRules = [
        'Numeric',
        'Integer',
    ];

    /**
     * Rules related to files.
     * 
     * @var array
     */
    protected array $fileRules = [
        'Size',
        'Between',
        'Min',
        'Max',
        'Dimensions',
        'File',
        'Image',
    ];

    /**
     * Rules related to implicit.
     * 
     * @var array
     */
    protected array $implicitRules = [
        'Accepted',
        'Declined',
        'Present',
        'Filled',
        'Required', 
        'RequiredIf',
        'RequiredUnless',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
    ];

    /**
     * Rules related to dependency.
     * 
     * @var array
     */
    protected array $dependentRules = [
        'After',
        'Before',
        'Different',
        'Match',
        'RequiredIf',
        'RequiredUnless',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
        'Unique',
    ];

    /**
     * Validator constructor
     */
    public function __construct(array $data, array $rules) 
    {
        $this->messages = new MessageBag;
        $this->data = new Container($data);

        $this->setRules($rules);
    }

    /**
     * Start validation and only return selecteWd keys.
     * 
     * @param string ...$keys
     * 
     * @return array|null
     */
    public function failsafe(string ...$keys): ?array
    {
        return ! empty(func_get_args())
            ? Arr::only($this->validated(), ...$keys)
            : $this->validated();
    }

    /**
     * Start the validation process and check if it fails.
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * Start the validation process and check if it validates.
     * 
     * @return bool
     */
    public function validates(): bool
    {
        return $this->passes();
    }

    /**
     * Return validated and valid data. (Starts validation)
     * 
     * @return array
     */
    public function validated(): array
    {
        if(! $this->validationProcessed) {
            $this->passes();
        }

        return $this->validated;
    }

    /**
     * Return the validated and invalid data. (Starts validation)
     * 
     * @return array
     */
    public function invalidated(): array
    {
        if(! $this->validationProcessed) {
            $this->passes();
        }

        return $this->failed;
    }

    /**
     * Return validated and valid data.
     * 
     * @return array
     */
    public function valid(): array
    {
        return $this->validated;
    }

    /**
     * Return the validated and failed keys.
     * 
     * @return array
     */
    public function failed(): array
    {
        return $this->failed;
    }

    /**
     * Get the error messages.
     * 
     * @return MessageBag
     */
    public function errors() : MessageBag 
    {
        return $this->messages;
    }

    /**
     * Get the identifiers that are being validated.
     * 
     * @return array
     */
    public function identifiers() : array
    {
        return $this->data->all();
    }

    /**
     * Get the rules stated.
     * 
     * @return array
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Set validator to stop on first failure.
     * 
     * @return Validator
     */
    public function strict(): Validator 
    {
        $this->stopOnFail = true;

        return $this;
    }

    /**
     * Set an identifier to be excluded from validation.
     * 
     * @param string $identifier
     * 
     * @return Validator
     */
    public function exclude(string $identifier): Validator 
    {
        $this->excluded[] = $identifier;

        return $this;
    }

    /**
     * Set custom messages on fails.
     * 
     * @param array $messages
     * 
     * @return Validator
     */
    public function messages(array $messages): Validator 
    {
        $this->customMessages = array_merge($this->customMessages, $messages);

        return $this;
    }

    /**
     * Set the translator object to get messages.
     * 
     * @param Translator $translator
     * 
     * @return void
     */
    public function setTranslator(Translator $translator): void 
    {
        $this->translator = $translator;
    }
    
    /**
     * Set the database verifier to verify database rules.
     * 
     * @param DatabaseVerifier $verifier
     * 
     * @return void
     */
    public function setDatabaseVerifier(DatabaseVerifier $verifier): void 
    {
        $this->verifier = $verifier;
    }

    /**
     * Return the database verifier.
     * 
     * @return DatabaseVerifier
     */
    public function getDatabaseVerifier(): DatabaseVerifier 
    {
        if(! $this->verifier instanceof DatabaseVerifier) {
            throw new RuntimeException("No database verifier has been set.");
        }

        return $this->verifier; 
    }

    /**
     * Parse the | seperator and set the rules.
     * 
     * @param array $rules
     * 
     * @return void
     */
    public function setRules(array $rules): void 
    {
        foreach($rules as $identifier => $rule) {
            $this->rules[$identifier] = $this->parseRules($rule);
        }
    }

    /**
     * Run identifier validations.
     * 
     * @return bool
     */
    public function passes(): bool
    {
        foreach($this->rules as $identifier => $rules) {

            // If the identifier is an exception to validation remove it.
            if($this->isException($identifier)) {
                $this->forgetIdentifier($identifier);

                continue;
            }

            if(! $this->messages->empty() && $this->stopOnFail) {
                break;
            }

            foreach($rules as $rule) {

                // Attempt to process the rules.
                if(! $this->validateIdentifier($identifier, $rule)) {

                    // Push validation to the failed stack.
                    $this->failed[$identifier][$rule] = 'failed';
                }

                // Add successfull validation to validated stack.
                $this->validated[$identifier][$rule] = 'validated';

                // If we should stop validation.
                if($this->shouldStopValidation($identifier)) {
                    break;
                }
            }
        }

        return empty($this->failed);
    }

    /**
     * Check if the identifier is excluded from validation.
     * 
     * @param string $identifier
     * 
     * @return bool
     */
    protected function isException(string $identifier): bool
    {
        foreach($this->excluded as $excluded) {
            if($excluded === $identifier || str_starts_with($excluded,  $identifier.'.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove the identifier from the rules.
     * 
     * @param string $identifier
     * 
     * @return void
     */
    protected function forgetIdentifier(string $identifier): void
    {
        $this->data->delete($identifier);
        unset($this->rules[$identifier]);
    }

    /**
     * Attempt to validate the identifier with the specified rules.
     * 
     * @param string $identifier
     * @param string $rule
     * 
     * @return bool
     */
    protected function validateIdentifier(string $identifier, string $rule): bool
    {
        [$rule, $options] = $this->parseOptions($rule);

        // If its an invalid rule return.
        if(empty($rule)) {
            return true;
        }

        $value = $this->getIdentifierValue($identifier);

        // If we are working with a file check if there are any file errors.
        if($value instanceof UploadedFile && 
            ! $value->isValid() && 
            $this->hasRule($identifier, $this->fileRules)
        ) {
            return $this->logFailure($identifier, $rule, $options);
        }

        // Check if we should validate the rule and attempt to validate.
        if($this->isValidatable($identifier, $rule, $value)) {

            $method = "validate{$rule}";
            if(! method_exists($this, $method) ) {
                throw new NotSupportedException(sprintf("Rule %s is not supported by the validator", $rule));
            }

            if(! $this->{$method}($identifier, $value, $options)) {
                return $this->logFailure($identifier, $rule, $options);
            }
        }

        return true;
    }

    /**
     * Get the value from data for an identifier.
     * 
     * @param string $identifier
     * 
     * @return mixed
     */
    public function getIdentifierValue(string $identifier): mixed
    {
        return $this->data->get($identifier);
    }

    /**
     * Check if an identifier has a rule.
     * 
     * @param string $identifier
     * @param array|null $rules
     * 
     * @return bool
     */
    public function hasRule(string $identifier, array $rules = null): bool
    {
        return ! is_null($this->getRule($identifier, $rules));
    }

    /**
     * Get the rules for an identifier.
     * 
     * @param string $identifier
     * @param array|null $rules
     * 
     * @return array|null
     */
    public function getRule(string $identifier, array $rules = null): ?array
    {
        // If there is no active rule set return empty.
        if(! array_key_exists($identifier, $this->rules)) {
            return null;
        }

        if(! is_null($rules)) {
            foreach($this->rules[$identifier] as $rule) {
                [$rule, $options] = $this->parseOptions($rule);
        
                if(in_array($rule, $rules)) {
                    return [$rule, $options];
                }
            }

            return null;
        }

        return $this->parseOptions($this->rules[$identifier]);
    }

    /**
     * Analyze options and rules to check if we actually require validation
     * 
     * @param string $identifier
     * @param string $rule
     * @param mixed $value
     * 
     * @return bool
     */
    protected function isValidatable(string $identifier, string $rule, mixed $value): bool 
    {
                // If the identifier exists in the data or the rule is implicit.
        return $this->identifierIsPresentOrImplicit($identifier, $rule, $value) &&
               // If the identifier is nullable and is not null.
               $this->isNotNullIfNullable($identifier, $rule) &&
               // If we have any database validation check if there was any previous error
               $this->isStillValidForDatabaseValidation($identifier, $rule);
    }
    
    /**
     * Check if the identifier is present or implicit.
     * 
     * @param string $identifier
     * @param string $rule
     * @param mixed|null $value
     * 
     * @return bool
     */
    protected function identifierIsPresentOrImplicit(string $identifier, string $rule, mixed $value = null): bool
    {
        // If the value is there but empty and is implicit.
        if(is_string($value) && trim($value) === '') {
            return $this->isImplicit($rule);
        }

        return $this->validatePresent($identifier) || $this->isImplicit($rule);
    }

    /**
     * Check if a rule is implicit.
     * 
     * @param string $rule
     * 
     * @return bool
     */
    protected function isImplicit(string $rule): bool
    {
        return in_array($rule, $this->implicitRules);
    }

    /**
     * @param string $identifier
     * @param string $rule
     * 
     * @return bool
     */
    protected function isNotNullIfNullable(string $identifier, string $rule): bool
    {
        if(! $this->isImplicit($rule) && $this->hasRule($identifier, ['Nullable'])) {
            // If the identifier doesnt exist then it should not be null
            return ! is_null($this->data->get($identifier));
        }

        return true;
    }

    /**
     * @param string $identifier
     * @param string $rule
     * 
     * @return bool
     */
    protected function isStillValidForDatabaseValidation(string $identifier, string $rule): bool
    {
        return in_array($rule, ['Unique', 'Exists']) ? ! $this->messages->has($identifier) : true;
    }

    /**
     * Log a failure message.
     * 
     * @param string $identifier
     * @param string $rule
     * @param array $parameters
     * 
     * @return bool
     */
    protected function logFailure(string $identifier, string $rule, array $options): bool 
    {
        $this->messages->set($identifier, 
            $this->getMessage($identifier, $rule, $options)
        );

        return false;
    }

    /**
     * Check if we should validate any furthur.
     * 
     * @param string $identifier
     * 
     * @return bool
     */
    protected function shouldStopValidation(string $identifier): bool 
    {
        // If an exit rule was set and we have an error.
        if($this->hasRule($identifier, ['Exit'])) {
            return $this->messages->has($identifier);
        }

        // If we have an uploaded rule that failed.
        if(isset($this->failed[$identifier]) && 
            array_key_exists('uploaded', $this->failed[$identifier])
        ){
            return true;
        }

        // if its optional but doesnt exist.
        if(! $this->hasRule($identifier, $this->implicitRules)) {
            return ! $this->validatePresent($identifier);
        }

        // If any implicit rules already failed.
        return $this->hasRule($identifier, $this->implicitRules) && 
            isset($this->failed[$identifier]) && 
            array_intersect_key(array_keys($this->failed[$identifier]), $this->implicitRules);
    }
}
