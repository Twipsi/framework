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

namespace Twipsi\Foundation\ComponentProviders;

use ReflectionException;
use Twipsi\Components\Translator\LocaleLocator;
use Twipsi\Components\Translator\Translator;
use Twipsi\Foundation\Application\Application;
use Twipsi\Foundation\ComponentProvider;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

class TranslatorProvider extends ComponentProvider
{
    /**
     * Register service provider.
     *
     * @return void
     * @throws ReflectionException
     * @throws ApplicationManagerException
     */
    public function register(): void
    {
        // Bind the translator to the application.
        $this->app->keep('translator', function (Application $app) {
            $locator = new LocaleLocator($app->path('path.locale'));

            $translator = new Translator($locator,
                $app->get('config')
                    ->get('system.locale', 'en')
            );

            $translator->attachUserToTranslator(function () {
                return call_user_func($this->app["auth.manager"]->getUserLoader());
            });

            return $translator;
        });
    }

    /**
     * The components provided.
     *
     * @return string[]
     */
    public function components(): array
    {
        return ['translator'];
    }
}
