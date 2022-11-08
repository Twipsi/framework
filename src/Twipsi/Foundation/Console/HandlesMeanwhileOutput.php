<?php

namespace Twipsi\Foundation\Console;

use Closure;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

trait HandlesMeanwhileOutput
{
    /**
     * Send a question to the user.
     *
     * @param string $question
     * @param string|null $default
     * @return mixed
     */
    public function ask(string $question, ?string $default = null): mixed
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Send a question to the user with auto-completion.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @return mixed
     */
    public function askQuestion(string $question, array $choices, ?string $default = null): mixed
    {
        $question = new Question($question, $default);
        $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Send a question to the user and hide answer.
     *
     * @param string $question
     * @param bool $fallback
     * @return mixed
     */
    public function secret(string $question, bool $fallback = true): mixed
    {
        $question = new Question($question);

        $question->setHidden(true)
            ->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Send a question to the user with a pack of possible answers.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @param int|null $attempts
     * @param bool $multiple
     * @return mixed
     */
    public function choice(string $question, array $choices, ? string $default = null, ?int $attempts = null, bool $multiple = false): mixed
    {
        $question = new ChoiceQuestion($question, $choices, $default);

        $question->setMaxAttempts($attempts)
            ->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format output to a table.
     *
     * @param array $headers
     * @param array $rows
     * @param TableStyle|string $tableStyle
     * @param array $columnStyles
     * @return void
     */
    public function table(array $headers, array $rows, TableStyle|string $tableStyle = 'default', array $columnStyles = []): void
    {
        $table = new Table($this->output);
        $table->setHeaders($headers)
            ->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $index => $style) {
            $table->setColumnStyle($index, $style);
        }

        $table->render();
    }

    /**
     * Create a progress bar while executing a callback.
     *
     * @param int|array $steps
     * @param Closure $callback
     * @return void
     */
    public function progress(int|array $steps, Closure $callback): void
    {
        $progress = $this->output->createProgressBar(
            is_array($steps) ? count($steps) : $steps
        );

        $progress->start();

        if(is_array($steps)) {
            foreach ($steps as $step) {
                $callback($step, $progress);
                $progress->advance();
            }
        } else {
            $callback($progress);
        }

        $progress->finish();
    }

    /**
     * Output a plain message.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function plain(string $message, int|string $verbosity = null): void
    {
        $this->output->writeln($message, $this->getVerbosity($verbosity));
    }

    /**
     * Output a comment message.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function comment(string $message, int|string $verbosity = null): void
    {
        $style = "<comment>$message</comment>";

        $this->output->writeln($style, $this->getVerbosity($verbosity));
    }

    /**
     * Output a question message.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function question(string $message, int|string $verbosity = null): void
    {
        $style = "<question>$message</question>";

        $this->output->writeln($style, $this->getVerbosity($verbosity));
    }

    /**
     * Output an info message.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function info(string $message, int|string $verbosity = null): void
    {
        $style = "<info>$message</info>";

        $this->output->writeln($style, $this->getVerbosity($verbosity));
    }

    /**
     * Output an error message.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function error(string $message, int|string $verbosity = null): void
    {
        $style = "<error>$message</error>";

        $this->output->writeln($style, $this->getVerbosity($verbosity));
    }

    /**
     * Output a warning message.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function warning(string $message, int|string $verbosity = null): void
    {
        $style = "<warning>$message</warning>";

        $this->output->writeln($style, $this->getVerbosity($verbosity));
    }

    /**
     * Output a notification box.
     *
     * @param string $message
     * @param int|null|string $verbosity
     * @return void
     */
    public function notification(string $message, int|string $verbosity = null): void
    {
        $length = strlen(strip_tags($message)) + 4;

        $this->comment(str_repeat('#', $length), $verbosity);
        $this->comment('#  $message  #', $verbosity);
        $this->comment(str_repeat('#', $length), $verbosity);

        $this->comment('', $verbosity);
    }

    /**
     * Output a new line.
     *
     * @param int $amount
     * @return void
     */
    public function line(int $amount = 1): void
    {
        $this->output->newLine($amount);
    }
}