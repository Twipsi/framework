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

namespace Twipsi\Components\Mailer;

use Twipsi\Components\View\ViewFactory;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Markdown
{
    /**
     * The theme to use.
     * 
     * @var string
     */
    protected string $theme = 'default';

    /**
     * Construct Markdown.
     * 
     * @param  protected
     * @param array $options
     */
    public function __construct(protected ViewFactory $factory, array $options = [])
    {
        $this->factory = $factory;

        if(isset($options['theme'])) {
            $this->theme = $options['theme'];
        }
    }

    /**
     * Render the mail file as html.
     * 
     * @param array $data
     * 
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        $mail = $this->factory->create($template, $data)->render();

        return (new CssToInlineStyles)->convert(
            $mail,
            $this->factory->create('mail.html.themes.'.$this->theme)->render()
        );
    }

    /**
     * Render the mail file as html.
     * 
     * @param array $data
     * 
     * @return string
     */
    public function renderText(string $template, array $data = []): string 
    {
        $mail = $this->factory->create($template, $data)->render();

        return html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n\n", $mail), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set the mail theme.
     * 
     * @param string $theme
     * 
     * @return void
     */
    public function theme(string $theme): void 
    {
        $this->theme = $theme;
    }
}