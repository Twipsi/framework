<?php

namespace Twipsi\Foundation\Console\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

class Plain extends RenderType
{
    /**
     * The info styles.
     *
     * @var array
     */
    protected array $style = [
    ];

    /**
     * The info mutators.
     *
     * @var array|string[]
     */
    protected array $mutators = [
        'mutatePunctuation',
        'mutatePaths',
    ];

    /**
     * Render an info message.
     *
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    public function render(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $message = $this->mutate($message, $this->mutators);

        $arguments = array_merge($this->style, ['content' => $message]);

        $this->buildView('plain', $arguments, $verbosity);
    }
}