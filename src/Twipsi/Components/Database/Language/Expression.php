<?php

namespace Twipsi\Components\Database\Language;

final class Expression
{
    /**
     * The expression saved.
     *
     * @var string
     */
    protected string $expression;

    /**
     * Construct the expression.
     *
     * @param string $expression
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * Get the expression.
     *
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }
}