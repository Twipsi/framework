<?php

namespace Twipsi\Foundation\Console\Renderer;

use Symfony\Component\Console\Output\OutputInterface;

class Success extends RenderType
{
    /**
     * The success styles.
     *
     * @var array
     */
    protected array $style = [
        'bgColor' => 'green',
        'fgColor' => 'white',
        'title' => 'success',
    ];

    /**
     * The success mutators.
     *
     * @var array|string[]
     */
    protected array $mutators = [
        'mutateHighlights',
        'mutatePunctuation',
        'mutatePaths',
    ];

    /**
     * Render a success message.
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