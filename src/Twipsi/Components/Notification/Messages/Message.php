<?php
/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Components\Notification\Messages;

use Twipsi\Support\Traits\Arrayable;
use Twipsi\Support\Traits\Serializable;

class Message
{
    use Arrayable, Serializable;

    /**
     * The message type (info, error, success, warning).
     * 
     * @var string
     */
    public string $level = 'info';

    /**
     * The subject of the message.
     * 
     * @var string
     */
    public string $subject;

    /**
     * The greeting of the message.
     * 
     * @var string
     */
    public string $greeting;

    /**
     * The footer of the message.
     * 
     * @var string
     */
    public string $footer;

    /**
     * Everything that becomes before the action.
     * 
     * @var array
     */
    public array $before = [];

    /**
     * Everything that comes after the action.
     * 
     * @var array
     */
    public array $after = [];

    /**
     * The name of the action to be introduced.
     * 
     * @var string
     */
    public string $action;

    /**
     * The url where the action should lead to.
     * 
     * @var string
     */
    public string $url;

    /**
     * The the message level.
     * 
     * @return Message
     */
    public function level(string $level): Message
    {
        $this->level = $level;

        return $this;
    }

    /**
     * The the message level to error.
     * 
     * @return Message
     */
    public function error(): Message
    {
        $this->level = 'error';

        return $this;
    }

    /**
     * The the message level to success.
     * 
     * @return Message
     */
    public function success(): Message
    {
        $this->level = 'success';

        return $this;
    }

    /**
     * The the message level to warning.
     * 
     * @return Message
     */
    public function warning(): Message
    {
        $this->level = 'warning';

        return $this;
    }

    /**
     * Set the message subject.
     * 
     * @param string $subject
     * 
     * @return Message
     */
    public function subject(string $subject): Message
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the greeting of the message.
     * 
     * @param string $greeting
     * 
     * @return Message
     */
    public function greeting(string $greeting): Message
    {
        $this->greeting = $greeting;

        return $this;
    }

    /**
     * Set the footer of the message.
     * 
     * @param string $footer
     * 
     * @return Message
     */
    public function footer(string $footer): Message
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Set the action name and url.
     * 
     * @param string $action
     * @param string $url
     * 
     * @return Message
     */
    public function action(string $action, string $url): Message
    {
        $this->action = $action;
        $this->url = $url;

        return $this;
    }

    /**
     * Set the before action content.
     * 
     * @param string $before
     * 
     * @return Message
     */
    public function before(string $before): Message
    {
        $this->before = $before;

        return $this;
    }

    /**
     * Set the after action content.
     * 
     * @param string $after
     * 
     * @return Message
     */
    public function after(string $after): Message
    {
        $this->after = $after;

        return $this;
    }

    /**
     * Add a row to the content of the message.
     * 
     * @param string $row
     * 
     * @return Message
     */
    public function row(string $row): Message
    {
        return $this->with($row);
    }

    /**
     * Add rows to the content of the message.
     * 
     * @param array $rows
     * 
     * @return Message
     */
    public function rows(array $rows): Message
    {
        foreach($rows as $row) {
            $this->with($row);
        }

        return $this;
    }

    /**
     * Add the actual row and parse data.
     * 
     * @param string $row
     * 
     * @return Message
     */
    public function with(string $row): Message
    {
        if(empty($this->action)) {
            $this->before[] = $this->parseRow($row);

        } else {
            $this->after[] = $this->parseRow($row);
        }

        return $this;
    }

    /**
     * Parse and format the row to be added 
     * trimming new lines from whitespace.
     * 
     * @param string $row
     * 
     * @return string
     */
    protected function parseRow(string $row): string
    {
        return implode(' ', array_map('trim', preg_split('/\r\n|\r|\n/', $row)));
    }

    /**
     * Return the message object as an array.
     * 
     * @return array
     */
    // protected function toArray(): array 
    // {
    //     return [
    //         'level' => $this->level,
    //         'subject' => $this->subject,
    //         'greeting' => $this->greeting,
    //         'before' => $this->before,
    //         'after' => $this->after,
    //         'action' => $this->action,
    //         'url' => $this->url
    //     ];
    // }
}