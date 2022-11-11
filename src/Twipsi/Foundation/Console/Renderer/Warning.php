<?php

namespace Twipsi\Foundation\Console\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

class Warning extends RenderType
{
    /**
     * The warning styles.
     *
     * @var array
     */
    protected array $style = [
        'bgColor' => 'yellow',
        'fgColor' => 'white',
        'title' => 'warning',
    ];

    /**
     * The warning mutators.
     *
     * @var array|string[]
     */
    protected array $mutators = [
        'mutateHighlights',
        'mutatePunctuation',
        'mutatePaths',
    ];

    /**
     * Render a warning message.
     *
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    public function render(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $message = $this->mutate($message, $this->mutators);

        $arguments = array_merge($this->style, ['content' => $message]);

        $this->buildView('styled', $arguments, $verbosity);
    }
}