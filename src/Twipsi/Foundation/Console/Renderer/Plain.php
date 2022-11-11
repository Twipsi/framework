<?php

namespace Twipsi\Foundation\Console\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

class Plain extends RenderType
{
    /**
     * The plain styles.
     *
     * @var array
     */
    protected array $style = [
    ];

    /**
     * The plain mutators.
     *
     * @var array|string[]
     */
    protected array $mutators = [
        'mutatePunctuation',
        'mutatePaths',
    ];

    /**
     * Render a plain message.
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