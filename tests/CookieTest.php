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

namespace Twipsi\Tests;

use PHPUnit\Framework\TestCase;
use Twipsi\Components\Cookie\Cookie;
use Twipsi\Support\Chronos;
use InvalidArgumentException;

final class CookieTest extends TestCase
{
  use CreatesApp;

  public function testCookieShouldBeBuildableFromString(): void
  {
    $string = 'twipsi_session=test-value;expires=Wed, 16-Feb-2022 09:47:59 GMT;max-age=172800; path=/;domain=;samesite=lax';

    $cookie = Cookie::fromString($string);

    $this->assertEquals('twipsi_session', $cookie->getName());
    $this->assertEquals('test-value', $cookie->getValue());
    $this->assertEquals(NULL, $cookie->getDomain());
    $this->assertEquals('/', $cookie->getPath());
    $this->assertEquals('lax', $cookie->getSameSite());

    $date = Chronos::date()
            ->setTimezone('GMT')
            ->addDays(2)
            ->setDateTimeFormat('D, d M Y H:i:s')
            ->getDateTime().' GMT';

    $this->assertEquals($date, $cookie->getExpiresAsDate());
  }

  public function testCookieShouldRefreshExpireIfMaxAgeIsSet(): void
  {
    $future = Chronos::date()->setTimezone('GMT')->addDays(5)->setDateTimeFormat('D, d M Y H:i:s')->getDateTime().' GMT';
    $past = Chronos::date()->setTimezone('GMT')->subDays(2)->setDateTimeFormat('D, d M Y H:i:s')->getDateTime().' GMT';

    $date = Chronos::date()->setTimezone('GMT')->addDays(2)->setDateTimeFormat('D, d M Y H:i:s')->getDateTime().' GMT';

    $past = 'twipsi_session=test-value;expires='.$past.';max-age=172800';
    $future = 'twipsi_session=test-value;expires='.$future.';max-age=172800';

    $cookie = Cookie::fromString($past);
    $this->assertEquals($date, $cookie->getExpiresAsDate());

    $cookie = Cookie::fromString($future);
    $this->assertEquals($date, $cookie->getExpiresAsDate());
  }

  public function testCookieShouldNeglectExpireIfMaxAgeIsZeroAndExpireIsInThePast(): void
  {
    $past = Chronos::date()->subDays(2)->setDateTimeFormat('D, d M Y H:i:s')->getDateTime().' GMT';
    $cookie = 'twipsi_session=test-value;expires='.$past.';max-age=0';

    $cookie = Cookie::fromString($cookie);
    $this->assertEquals($past, $cookie->getExpiresAsDate());
  }

  public function testCookieShouldSetExpireToNowIfMaxAgeIsZeroAndExpireIsInTheFuture(): void
  {
    $date = Chronos::date()->setTimezone('GMT')->setDateTimeFormat('D, d M Y H:i:s')->getDateTime().' GMT';
    $future = Chronos::date()->setTimezone('GMT')->addDays(2)->setDateTimeFormat('D, d M Y H:i:s')->getDateTime().' GMT';
    $cookie = 'twipsi_session=test-value;expires='.$future.';max-age=0';

    $cookie = Cookie::fromString($cookie);
    $this->assertEquals($date, $cookie->getExpiresAsDate());
  }

  public function testCookieShouldFilterIllegalNameCharacters(): void
  {
    $illegals = str_split('=,; ":\\[]!');

    foreach($illegals as $illegal) {
      $this->expectException(InvalidArgumentException::class);
      Cookie::fromString('twipsi_sess'.$illegal.'ion=test-value;');
    }
  }

  public function testCookieShouldNotAcceptIllegalSameSite(): void
  {
    $this->expectException(InvalidArgumentException::class);
    Cookie::fromString('twipsi_session=test-value;samesite=laxit');
  }

}
