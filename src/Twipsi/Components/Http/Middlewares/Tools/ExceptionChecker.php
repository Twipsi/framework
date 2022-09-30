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

namespace Twipsi\Components\Http\Middlewares\Tools;

use Twipsi\Support\Str;
use Twipsi\Support\Arr;
use Twipsi\Components\Url\UrlItem;

class ExceptionChecker
{
  /**
  * Check if url is in the exception list.
  */
  public static function url( UrlItem $url, array $exceptions ) : bool
  {
    return Arr::hay( $exceptions )->attempt( function( $exception ) use( $url ) {

      if( $exception !== '/' ) {
        $exception = '/'.rtrim( ltrim( $exception, '/' ), '/' );
      }

      // Check for multi subpaths using * at the end
      if( Str::hay( $exception )->last('*') ) {

        $exception = Str::hay( $exception )->remove( '*' );

        if( Str::hay( $url->getPath() )->resembles( $exception ) ) {
          return true;
        }
      }

      if( $exception === $url->getRelativeUrl( false ) ) {
        return true;
      }

    });
  }

}
