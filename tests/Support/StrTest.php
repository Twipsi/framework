<?php

namespace Twipsi\Tests\Support;

use PHPUnit\Framework\TestCase;
use Twipsi\Support\Str;

class StrTest extends TestCase
{
    /**
     * The test data.
     *
     * @var string
     */
    protected string $data;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->data =
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit dolor sit.';

        parent::setUp();
    }

    public function testConvertString()
    {
        $str = Str::hay($this->data)->convert('uc', 'UC');

        $this->assertSame(
            $str,
            'Lorem ipsUm dolor sit amet, ConseCtetUr adipisCing elit dolor sit.'
        );
    }

    public function testHeaderString()
    {
        $str = Str::hay('Cache_Control')->header();

        $this->assertSame(
            $str,
            'cache-control'
        );
    }

    public function testCamelizeString()
    {
        $str = Str::hay($this->data)->camelize(' ');

        $this->assertSame(
            $str,
            'Lorem Ipsum Dolor Sit Amet, Consectetur Adipiscing Elit Dolor Sit.'
        );
        $this->assertSame(
            Str::hay('thisisintel')->camelize('i'),
            'ThiSiSiNtel'
        );
        $this->assertSame(
            Str::hay('getname')->camelize('t'),
            'GetName'
        );
    }

    public function testCapitelizeString()
    {
        $str = Str::hay('passwordResetNotification')->capitelize();

        $this->assertSame(
            $str,
            'Password Reset Notification'
        );
    }

    public function testSnakeString()
    {
        $str = Str::hay('passwordResetNotification')->snake();

        $this->assertSame(
            $str,
            'password_reset_notification'
        );
    }

    public function testWrapString()
    {
        $str = Str::hay($this->data)->wrap('"');

        $this->assertSame(
            $str,
            '"Lorem ipsum dolor sit amet, consectetur adipiscing elit dolor sit."'
        );
    }

    public function testPullString()
    {
        $str = Str::hay($this->data)->pull(10, 25);

        $this->assertSame(
            $str,
            'm dolor sit amet, consect'
        );
    }

    public function testSliceStartString()
    {
        $str = Str::hay($this->data)->sliceStart('Lorem');

        $this->assertSame(
            $str,
            ' ipsum dolor sit amet, consectetur adipiscing elit dolor sit.'
        );
    }

    public function testSliceEndString()
    {
        $str = Str::hay($this->data)->sliceEnd(' elit dolor sit.');

        $this->assertSame(
            $str,
            'Lorem ipsum dolor sit amet, consectetur adipiscing'
        );
    }

    public function testTransliterateString()
    {
        $str = Str::hay('éáűúőöüóabcí')->transliterate();

        $this->assertSame(
            $str,
            'eauuoouoabci'
        );
    }

    public function testFirstString()
    {
        $str = Str::hay($this->data)->first();

        $this->assertSame(
            $str,
            'L'
        );
        $this->assertTrue(
            Str::hay($this->data)->first('L')
        );
        $this->assertFalse(
            Str::hay($this->data)->first('o')
        );
    }

    public function testLastString()
    {
        $str = Str::hay($this->data)->last();

        $this->assertSame(
            $str,
            '.'
        );
        $this->assertTrue(
            Str::hay($this->data)->last('.')
        );
        $this->assertFalse(
            Str::hay($this->data)->last('o')
        );
    }

    public function testNumericString()
    {
        $this->assertTrue(
            Str::hay('25418')->numeric()
        );
        $this->assertFalse(
            Str::hay('541gda14')->numeric()
        );
    }

    public function testAlnumString()
    {
        $this->assertTrue(
            Str::hay('abcdef')->alnum()
        );
        $this->assertTrue(
            Str::hay('541gda14')->alnum()
        );
        $this->assertFalse(
            Str::hay('5522,:_1144')->alnum()
        );
    }

    public function testIndexString()
    {
        $this->assertSame(
            Str::hay($this->data)->index('Sit', true), -1
        );
        $this->assertSame(
            Str::hay($this->data)->index('sit', true), 18
        );
        $this->assertSame(
            Str::hay($this->data)->index('sit'), 18
        );
        $this->assertSame(
            Str::hay($this->data)->index('Sit'), 18
        );
    }

    public function testRemoveString()
    {
        $this->assertSame(
            Str::hay($this->data)->remove(',',' ', 'Lorem'),
            'ipsumdolorsitametconsecteturadipiscingelitdolorsit.'
        );
    }

    public function testReplaceString()
    {
        $this->assertSame(
            Str::hay($this->data)->replace([' ', 'c'], ['$', 'C']),
            'Lorem$ipsum$dolor$sit$amet,$ConseCtetur$adipisCing$elit$dolor$sit.'
        );
        $this->assertSame(
            Str::hay($this->data)->replace([' ', 'c'], '$'),
            'Lorem$ipsum$dolor$sit$amet,$$onse$tetur$adipis$ing$elit$dolor$sit.'
        );
    }

    public function testHasString()
    {
        $this->assertTrue(
            Str::hay($this->data)->has('sit')
        );
        $this->assertTrue(
            Str::hay($this->data)->has('Lorem')
        );
        $this->assertFalse(
            Str::hay($this->data)->has('lorem')
        );
        $this->assertFalse(
            Str::hay($this->data)->has('hello')
        );
    }

    public function testContainsString()
    {
        $this->assertTrue(
            Str::hay($this->data)->contains(',.')
        );
        $this->assertTrue(
            Str::hay($this->data)->contains(',:')
        );
        $this->assertFalse(
            Str::hay($this->data)->contains('@:')
        );
        $this->assertTrue(
            Str::hay($this->data)->contains('lorem')
        );
        $this->assertFalse(
            Str::hay($this->data)->contains('ű')
        );
    }

    public function testResemblesString()
    {
        $this->assertTrue(
            Str::hay($this->data)->resembles('lorem', 'dolor', 'amet')
        );
        $this->assertTrue(
            Str::hay($this->data)->resembles('lorem', 'hello', 'amet')
        );
        $this->assertFalse(
            Str::hay($this->data)->resembles('lorems', 'hellos', 'amets')
        );
    }

    public function testSlugifyString()
    {
        $this->assertSame(
            Str::hay('Aőüö IS bad')->slugify('-'),
            'aouo-is-bad'
        );
        $this->assertSame(
            Str::hay('Aőüö IS bad')->slugify('_'),
            'aouo_is_bad'
        );
    }

    public function testAfterString()
    {
        $this->assertSame(
            Str::hay($this->data)->after('dolor'),
            ' sit amet, consectetur adipiscing elit dolor sit.'
        );
        $this->assertSame(
            Str::hay($this->data)->after('consectetur'),
            ' adipiscing elit dolor sit.'
        );
    }

    public function testAfterLastString()
    {
        $this->assertSame(
            Str::hay($this->data)->afterLast('dolor'),
            ' sit.'
        );
        $this->assertSame(
            Str::hay($this->data)->afterLast('adipiscing '),
            'elit dolor sit.'
        );
    }

    public function testBeforeString()
    {
        $this->assertSame(
            Str::hay($this->data)->before('dolor'),
            'Lorem ipsum '
        );
        $this->assertSame(
            Str::hay($this->data)->before('consectetur'),
            'Lorem ipsum dolor sit amet, '
        );
    }

    public function testBeforeLastString()
    {
        $this->assertSame(
            Str::hay($this->data)->beforeLast('dolor'),
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit '
        );
        $this->assertSame(
            Str::hay($this->data)->beforeLast('adipiscing '),
            'Lorem ipsum dolor sit amet, consectetur '
        );
    }

    public function testBetweenString()
    {
        $this->assertSame(
            Str::hay('Hello {this} is {that}')->between('{', '}'),
            [
                'this',
                'that'
            ]
        );
        $this->assertSame(
            Str::hay('Hello {this} is {that}')->between('{', ' '),
            [
                'this}',
            ]
        );
    }

    public function testBetweenFirstString()
    {
        $this->assertSame(
            Str::hay('Hello {this} is {that}')->betweenFirst('{', '}'),
            'this'
        );
    }

    public function testBetweenLastString()
    {
        $this->assertSame(
            Str::hay('Hello {this} is {that}')->betweenLast('{', '}'),
            'that'
        );
    }
}