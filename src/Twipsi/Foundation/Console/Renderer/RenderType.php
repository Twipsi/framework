<?php

namespace Twipsi\Foundation\Console\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twipsi\Facades\App;
use Twipsi\Support\Str;
use function Termwind\render;
use function Termwind\renderUsing;

abstract class RenderType
{
    /**
     * The output symfony style.
     *
     * @var SymfonyStyle
     */
    protected SymfonyStyle $output;

    /**
     * Construct the command renderer.
     */
    public function __construct(SymfonyStyle $output)
    {
        $this->output = $output;
    }

    /**
     * Build the view output.
     *
     * @param string $type
     * @param array $arguments
     * @param int $verbosity
     * @return void
     */
    protected function buildView(string $type, array $arguments, int $verbosity): void
    {
        extract($arguments);
        ob_start();

        include __DIR__."/../Views/$type.php";

        $view = ob_get_contents();
        ob_end_clean();

        renderUsing($this->output);
        render($view, $verbosity);
    }

    /**
     * Mutate a message based on provided mutators.
     *
     * @param string $message
     * @param array $mutators
     * @return string
     */
    protected function mutate(string $message, array $mutators): string
    {
        foreach ($mutators as $mutator) {
            if(method_exists($this, $mutator)) {
                $message = $this->{$mutator}($message);
            }
        }

        return trim($message);
    }

    /**
     * Highlight content in the string.
     *
     * @param string $message
     * @return string
     */
    protected function mutateHighlights(string $message): string
    {
        return preg_replace('/\[([^]]+)]/', '<options=bold>[$1]</>', $message);
    }

    /**
     * Add punctuation to the end.
     *
     * @param string $message
     * @return string
     */
    protected function mutatePunctuation(string $message): string
    {
        if(! in_array(Str::hay($message)->last(), ['.', '?', '!'])) {
            $message = $message.'.';
        }

        return $message;
    }

    /**
     * Remove base paths from any string.
     *
     * @param string $message
     * @return string
     */
    protected function mutatePaths(string $message): string
    {
        $base = App::path('path.base').'/';

        return str_replace($base, '', $message);
    }

    /**
     * The abstract render method.
     *
     * @param string $message
     * @param int $verbosity
     * @return void
     */
    abstract function render(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void;
}