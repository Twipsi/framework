<?php

namespace Twipsi\Tests\Bags;

use InvalidArgumentException;
use Twipsi\Support\Bags\ArrayBag;
use PHPUnit\Framework\TestCase;

class ArrayBagTest extends TestCase
{
    /**
     * The test data.
     *
     * @var array
     */
    protected array $data;

    /**
     * The recursive test data.
     *
     * @var array
     */
    protected array $rdata;

    /**
     * The test collection.
     *
     * @var ArrayBag
     */
    protected ArrayBag $collection;

    /**
     * The recursive test collection.
     *
     * @var ArrayBag
     */
    protected ArrayBag $r_collection;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->data = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
        ];

        $this->rdata = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
            'dot.dotted' => 'single',
            'recursive' => [
                'aaa',
                'zzz',
            ],
            'recursive_associate' => [
                'x' => 'xxx',
                'y' => 'yyy',
            ],
            'multi_recursive' => [
                'mmm' => [
                    'aaa',
                    'zzz',
                ],
            ],
            'multi_recursive_associate' => [
                'mmm' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
            ],
        ];

        $this->collection = new ArrayBag($this->data);
        $this->r_collection = new ArrayBag($this->rdata);

        parent::setUp();
    }

    // DONE
    public function testCloneInstanceWithCallback()
    {
        $collection = $this->r_collection
            ->clone(function($collection) {
                return $collection
                    ->filter(fn($v) => !is_null($v) && $v !== true && !is_array($v));
            });

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'dot.dotted' => 'single'
            ]
        );
    }

    // DONE
    public function testReplaceParameters()
    {
        $collection = (clone($this->collection))
            ->replace(['a' => 'a.a']);

        $this->assertSame(
            $collection->get('a'), 'a.a'
        );
        $this->assertNull(
            $collection->get('foo')
        );
    }

    // DONE
    public function testMergeParameters()
    {
        $collection = (clone($this->collection))
            ->merge(['a' => 'a.a']);

        $collection2 = (clone($this->collection))
            ->merge(['a' => ['a.a', 'b.b']]);

        $collection3 = (new ArrayBag(['a' => ['a.a', 'b.b']]))
            ->merge(['a' => ['c.c', 'd.d'], 5]);

        $this->assertSame(
            $collection->get('a'), 'a.a'
        );
        $this->assertSame(
            $collection->get('bar'), 'baz'
        );
        $this->assertSame(
            $collection2->get('a'), ['a.a', 'b.b']
        );
        $this->assertSame(
            $collection3->all(), ['a' => ['a.a', 'b.b', 'c.c', 'd.d'], 5]
        );
    }

    // DONE
    public function testSetParameters()
    {
        $collection = (clone($this->r_collection))
            ->set('yolo', 'new')
            ->set('multi_recursive_associate.mmm.h', 'zzz');

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                        'h' => 'zzz',
                    ],
                ],
                'yolo' => 'new'
            ]
        );
    }

    // DONE
    public function testAddParameters()
    {
        $collection = (clone($this->r_collection))
            ->add('yolo');

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
                0 => 'yolo',
            ]
        );
    }

    // DONE
    public function testPrependParameters()
    {
        $collection = (clone($this->r_collection))
            ->prepend('yolo')
            ->prepend('yolo', 'new')
            ->prepend('yolo', 'multi_recursive_associate.mmm');

        $this->assertSame(
            $collection->all(),
            [
                'new' => 'yolo',
                0 => 'yolo',
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        0 => 'yolo',
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );

        try {
            (clone($this->r_collection))
                ->prepend('yolo', 'this.is.new');
        } catch ( InvalidArgumentException ) {
            $error = true;
        }

        $this->assertTrue($error ?? false);
    }

    // DONE
    public function testPushParameters()
    {
        $collection = (clone($this->r_collection))
            ->push('recursive', 'yolo')
            ->push('this.is', 'yolo')
            ->push('multi_recursive_associate.mmm', 'yolo');

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                    'yolo',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                        0 => 'yolo',
                    ],
                ],
                'this' => [
                    'is' => [
                        'yolo',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testHasParameters()
    {
        $this->assertTrue(
            $this->r_collection->has('foo')
        );
        $this->assertFalse(
            $this->r_collection->has('yolo')
        );
        $this->assertTrue(
            $this->r_collection->has('foo', 'baz')
        );
        $this->assertFalse(
            $this->r_collection->has('foo', 'baz', 'yolo')
        );
        $this->assertTrue(
            $this->r_collection->has('multi_recursive_associate.mmm.x')
        );
        $this->assertFalse(
            $this->r_collection->has('multi_recursive_associate.mmm.u')
        );
    }

    // DONE
    public function testHasAnyParameters()
    {
        $this->assertTrue(
            $this->r_collection->hasAny('foo')
        );
        $this->assertFalse(
            $this->r_collection->hasAny('yolo')
        );
        $this->assertTrue(
            $this->r_collection->hasAny('foo', 'baz')
        );
        $this->assertTrue(
            $this->r_collection->hasAny('foo', 'baz', 'yolo')
        );
        $this->assertFalse(
            $this->r_collection->hasAny('yolo', 'hello')
        );
        $this->assertTrue(
            $this->r_collection->hasAny('multi_recursive_associate.mmm.x', 'multi_recursive_associate.mmm.u')
        );
        $this->assertFalse(
            $this->r_collection->hasAny('multi_recursive_associate.mmm.u', 'multi_recursive_associate.mmm.n')
        );
    }

    // DONE
    public function testFindParameters()
    {
        $this->assertTrue(
            $this->r_collection->find('foo')
        );
        $this->assertFalse(
            $this->r_collection->find('yolo')
        );
        $this->assertTrue(
            $this->r_collection->find('foo', 'baz')
        );
        $this->assertFalse(
            $this->r_collection->find('foo', 'baz', 'yolo')
        );
        $this->assertTrue(
            $this->r_collection->find('x')
        );
        $this->assertFalse(
            $this->r_collection->find('u')
        );
    }

    // DONE
    public function testFindAnyParameters()
    {
        $this->assertTrue(
            $this->r_collection->findAny('foo')
        );
        $this->assertFalse(
            $this->r_collection->findAny('yolo')
        );
        $this->assertTrue(
            $this->r_collection->findAny('foo', 'baz')
        );
        $this->assertTrue(
            $this->r_collection->findAny('foo', 'baz', 'yolo')
        );
        $this->assertFalse(
            $this->r_collection->findAny('yolo', 'hello')
        );
        $this->assertTrue(
            $this->r_collection->findAny('x', 'u')
        );
        $this->assertFalse(
            $this->r_collection->findAny('u', 'n')
        );
    }

    // DONE
    public function testDeleteParameters()
    {
        $collection = (clone($this->r_collection))
            ->delete('foo', 'baz', 'tap', 'recursive_associate.x', 'multi_recursive_associate.mmm.y');

        $this->assertSame(
            $collection->all(),
            [
                'bar' => 'baz',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testRejectParameters()
    {
        $collection = (new ArrayBag([1,2,3,4,5]))
            ->reject(function ($value, $key) {
                return $value > 2;
            });

        $collection2 = (clone($this->r_collection))
            ->reject(function ($value, $key) {
                return !is_array($value) && $value !== 'bat' && $value !== 'xxx';
            });

        $collection3 = (clone($this->r_collection))
            ->reject(function ($value, $key) {
                return $value === 'xxx';
            });

        $collection4 = (clone($this->r_collection))
            ->reject(function ($value, $key) {
                return is_array($value);
            });

        $this->assertSame(
            $collection->all(), [1,2]
        );
        $this->assertSame(
            $collection2->get('baz'), 'bat'
        );
        $this->assertNull(
            $collection2->get('foo')
        );
        $this->assertNull(
            $collection2->get('bar')
        );
        $this->assertSame(
            $collection2->all(),
            [
                'baz' => 'bat',
                'recursive_associate' => [
                    'x' => 'xxx',
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection3->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection4->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single'
            ]
        );
    }

    // DONE
    public function testForgetParameters()
    {
        $collection = (clone($this->collection));

        $deleted = $this->r_collection
            ->forget('foo', 'baz', 'tap', 'recursive_associate.x', 'multi_recursive_associate.mmm.y');

        $this->assertNotSame(
            [
                'bar' => 'baz',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                    ],
                ],
            ],
            $collection->all()
        );
        $this->assertSame(
            $deleted->all(),
            [
                'bar' => 'baz',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testAllParameters()
    {
        $this->assertSame(
            $this->r_collection->all(), $this->rdata
        );
    }

    // DONE
    public function testAllParametersWithExceptions()
    {
        $data = $this->rdata;
        unset($data['foo'], $data['bar'], $data['baz'],
            $data['recursive']['aaaaa'], $data['multi_recursive_associate']['mmm']['x']);

        $this->assertSame(
            $this->r_collection->all('foo', 'bar', 'baz',
                'recursive.aaaaa', 'multi_recursive_associate.mmm.x'),
            $data
        );
    }

    // DONE
    public function testAllSelectedParameters()
    {
        $this->assertSame(
            $this->r_collection->selected('foo', 'bar', 'baz')
                ->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
            ]
        );
        $this->assertSame(
            $this->r_collection->selected('foo', 'bar', 'baz', 'mmm')
                ->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testGetParameters()
    {
        $collection = (clone($this->r_collection));

        $this->assertSame(
            $collection->get('foo'), 'bar'
        );
        $this->assertSame(
            $collection->get('multi_recursive_associate.mmm.x'), 'xxx'
        );
        $this->assertSame(
            $collection->get('yolo', '404'), '404'
        );
        $this->assertNull(
            $collection->get('yolo')
        );
    }

    // DONE
    public function testPullParameters()
    {
        $collection = (clone($this->r_collection));

        $pulled = $collection->pull('foo');
        $pulled2 = $collection->pull('multi_recursive_associate.mmm.x');

        $this->assertSame(
            $collection->all(),
            [
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $pulled, 'bar'
        );
        $this->assertSame(
            $pulled2, 'xxx'
        );
    }

    // DONE
    public function testPopParameters()
    {
        $collection = (clone($this->r_collection));
        $popped = $collection->pop();
        $popped2 = $collection->pop('recursive_associate');

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $popped,
            [
                'mmm' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
            ]
        );
        $this->assertSame(
            $popped2,
            'yyy',
        );

        try {
            $collection->pop('recursive_associate.not.exist');
        } catch (InvalidArgumentException) {
            $error = true;
        }

        $this->assertTrue($error ?? false);
    }

    // DONE
    public function testParametersKeys()
    {
        $this->assertSame(
            $this->r_collection->keys()->all(),
            [
                'foo',
                'bar',
                'baz',
                'null',
                'boolean',
                'dot.dotted',
                [
                    0,
                    1,
                ],
                [
                    'x',
                    'y',
                ],
                [
                    [
                        0,
                        1,
                    ],
                ],
                [
                    [
                        'x',
                        'y',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $this->r_collection->keys(true)->all(),
            [
                0 => 'foo',
                1 => 'bar',
                2 => 'baz',
                3 => 'null',
                4 => 'boolean',
                5 => 'dot.dotted',
                6 => 0,
                7 => 1,
                8 => 'x',
                9 => 'y',
                10 => 0,
                11 => 1,
                12 => 'x',
                13 => 'y',
            ]
        );
    }

    // DONE
    public function testParametersValues()
    {
        $this->assertSame(
            $this->r_collection->values()->all(),
            [
                'bar',
                'baz',
                'bat',
                null,
                true,
                'single',
                [
                    'aaa',
                    'zzz',
                ],
                [
                    'xxx',
                    'yyy',
                ],
                [
                    [
                        'aaa',
                        'zzz',
                    ],
                ],
                [
                    [
                        'xxx',
                        'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $this->r_collection->values(true)->all(),
            [
                0 => 'bar',
                1 => 'baz',
                2 => 'bat',
                3 => null,
                4 => true,
                5 => 'single',
                6 => 'aaa',
                7 => 'zzz',
                8 => 'xxx',
                9 => 'yyy',
                10 => 'aaa',
                11 => 'zzz',
                12 => 'xxx',
                13 => 'yyy',
            ]
        );
    }

    // DONE
    public function testLonelyParameters()
    {
        $this->assertFalse(
            $this->r_collection->lonely()
        );
        $this->assertTrue(
            $this->r_collection->replace([1])->lonely()
        );
        $this->assertFalse(
            $this->r_collection->replace([])->lonely()
        );
    }

    // DONE
    public function testEmptyParameters()
    {
        $this->assertFalse(
            $this->r_collection->empty()
        );
        $this->assertTrue(
            $this->r_collection->replace([])->empty()
        );
    }

    // DONE
    public function testCountParameters()
    {
        $this->assertSame(
            $this->r_collection->count(), 20
        );

        $this->assertSame(
            $this->r_collection->count('multi_recursive_associate.mmm'), 2
        );

        $this->assertSame(
            $this->r_collection->count('recursive_associate'), 2
        );
    }

    // DONE
    public function testSumParameters()
    {
        $collection = new ArrayBag([1,[2, 'xxx' => [3,4]],5]);

        $this->assertSame(
            $collection->sum(), 15
        );
        $this->assertSame(
            $collection->sum('1.xxx'), 7
        );
        $this->assertSame(
            $collection->sum('1.xxx.mmm'), 0
        );
    }

    // DONE
    public function testAvgParameters()
    {
        $collection = new ArrayBag([1,[2, 'xxx' => [3,4]],5]);
        $collection2 = new ArrayBag([]);

        $this->assertSame(
            $collection->avg(), 3
        );
        $this->assertSame(
            $collection2->avg(), 0
        );
        $this->assertSame(
            $collection->avg('1.xxx'), 3.5
        );
        $this->assertSame(
            $collection->avg('1.xxx.mmm'), 0
        );
    }

    // DONE
    public function testMinParameters()
    {
        $collection = new ArrayBag(
            [1,[2, 'xxx' => ['yolo',3,4]],5]
        );

        $this->assertSame(
            $collection->min(), 1
        );
        $this->assertSame(
            $collection->min('1.xxx'), 3
        );
    }

    // DONE
    public function testMaxParameters()
    {
        $collection = new ArrayBag(
            [1,[2, 'xxx' => [3,4]],5]
        );

        $this->assertSame(
            $collection->max(), 5
        );
        $this->assertSame(
            $collection->max('1.xxx'), 4
        );
    }

    // DONE
    public function testFirstParameters()
    {
        $collection = (new ArrayBag([]));

        $this->assertSame(
            $this->r_collection->first(function ($value, $key) {
                return !is_string($value);
            }), null
        );
        $this->assertSame(
            $this->r_collection->first(), 'bar',
        );
        $this->assertFalse(
            $collection->first()
        );
        $this->assertSame(
            $this->r_collection->first(function ($value, $key) {
                return $key === 'mmm';
            }, true), 'aaa'
        );
    }

    // DONE
    public function testLastParameters()
    {
        $collection = (new ArrayBag([]));

        $this->assertSame(
            $this->r_collection->last(function ($value, $key) {
                return is_string($value);
            }), 'single'
        );
        $this->assertSame(
            $this->r_collection->last(function ($value, $key) {
                return is_string($value);
            }, true), 'yyy'
        );
        $this->assertSame(
            $this->r_collection->last(),
            [
                'mmm' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
            ]
        );
        $this->assertSame(
            $this->r_collection->last(null, true),
            'yyy'
        );
        $this->assertFalse(
            $collection->last()
        );
        $this->assertSame(
            $this->r_collection->last(function ($value, $key) {
                return $key === 'mmm';
            }, true), 'yyy'
        );
    }

    // DONE
    public function testSearchParameters()
    {
        $this->assertSame(
            $this->r_collection->search('baz'), 'bar'
        );
        $this->assertSame(
            $this->r_collection->search('true'), 'boolean'
        );
        $this->assertFalse(
            $this->r_collection->search('true', true)
        );
        $this->assertSame(
            $this->r_collection->search('yyy', true), 'recursive_associate.y'
        );
    }

    // DONE
    public function testExistsParameters()
    {
        $this->assertTrue(
            $this->r_collection->exists('bar', 'baz', 'xxx')
        );
        $this->assertFalse(
            $this->r_collection->exists('aaa', 'true')
        );
        $this->assertFalse(
            $this->r_collection->exists('mmm')
        );
        $this->assertFalse(
            $this->r_collection->exists('bar', 'baz', 'xxx', 'uuu')
        );
    }

    // DONE
    public function testExistsAnyParameters()
    {
        $this->assertTrue(
            $this->r_collection->existsAny('bar', 'rrr', 'nnn')
        );
        $this->assertFalse(
            $this->r_collection->existsAny('mmm', 'true')
        );
        $this->assertTrue(
            $this->r_collection->existsAny('mmm', 'aaa')
        );
        $this->assertTrue(
            $this->r_collection->existsAny('bar', 'baz', 'xxx', 'uuu')
        );
    }

    // DONE
    public function testDuplicateParameters()
    {
        $collection = new ArrayBag([
            'yellow',
            'red',
            'Red',
            'green',
            'YellOw',
            'blue',
            'red',
        ]);

        $collection2 = (clone($this->r_collection))
            ->set('recursive2', ['aaa', 'zzz']);

        $this->assertSame(
            $collection->duplicates(),
            [
                2 => 'red',
                4 => 'yellow'
            ]
        );

        $this->assertSame(
            $collection->duplicates(true),
            [
                6 => 'red',
            ]
        );

        $this->assertSame(
            $collection2->duplicates(true),
            ['recursive2' => ['aaa', 'zzz']]
        );
    }

    // DONE
    public function testParametersContainByClosure()
    {
        $collection = new ArrayBag([[1,2],3,[4,5,[6,7],8]]);

        $this->assertTrue(
            $this->r_collection->contains(function($value, $key) {
                return is_array($value);
            })
        );

        $this->assertFalse(
            $this->r_collection->contains(function($value, $key) {
                return is_numeric($value);
            })
        );
        $this->assertTrue(
            $this->r_collection->contains(function($value, $key) {
                return is_bool($value);
            })
        );
        $this->assertTrue(
            $collection->contains(function($value, $key) {
                return $value > 7;
            })
        );
        $this->assertTrue(
            $collection->contains(function($value, $key) {
                return $value < 2;
            })
        );
    }

    // DONE
    public function testParametersContainByPair()
    {
        $this->assertTrue(
            $this->r_collection->contains(['baz' => 'bat'])
        );

        $this->assertFalse(
            $this->r_collection->contains(['foo' => 'bat'])
        );

        $this->assertFalse(
            $this->r_collection->contains(['baz' => 'bar'])
        );

        $this->assertFalse(
            $this->r_collection->contains(['bag' => 'bav'])
        );
        $this->assertTrue(
            $this->r_collection->contains(['recursive_associate.x' => 'xxx'])
        );
        $this->assertTrue(
            $this->r_collection->contains(['multi_recursive_associate.mmm' => ['x' => 'xxx', 'y' => 'yyy']])
        );
    }

    // DONE
    public function testParametersContainByValue()
    {
        $this->assertTrue(
            $this->r_collection->contains('bat')
        );
        $this->assertTrue(
            $this->r_collection->contains(null)
        );
        $this->assertTrue(
            $this->r_collection->contains(true)
        );
        $this->assertFalse(
            $this->r_collection->contains('far')
        );
        $this->assertFalse(
            $this->r_collection->contains(false)
        );
        $this->assertFalse(
            $this->r_collection->contains(5)
        );
        $this->assertTrue(
            $this->r_collection->contains('xxx')
        );
        $this->assertFalse(
            $this->r_collection->contains('uuu')
        );
    }

    // DONE
    public function testParametersMissingByClosure()
    {
        $collection = new ArrayBag([[1,2],3,[4,5,[6,7],8]]);

        $this->assertFalse(
            $this->r_collection->missing(function($value, $key) {
                return is_array($value);
            })
        );

        $this->assertTrue(
            $this->r_collection->missing(function($value, $key) {
                return is_numeric($value);
            })
        );
        $this->assertFalse(
            $this->r_collection->missing(function($value, $key) {
                return is_bool($value);
            })
        );
        $this->assertFalse(
            $collection->missing(function($value, $key) {
                return $value > 7;
            })
        );
        $this->assertFalse(
            $collection->missing(function($value, $key) {
                return $value < 2;
            })
        );
    }

    // DONE
    public function testParametersMissingByPair()
    {
        $this->assertFalse(
            $this->r_collection->missing(['baz' => 'bat'])
        );

        $this->assertTrue(
            $this->r_collection->missing(['foo' => 'bat'])
        );

        $this->assertTrue(
            $this->r_collection->missing(['baz' => 'bar'])
        );

        $this->assertTrue(
            $this->r_collection->missing(['bag' => 'bav'])
        );
        $this->assertFalse(
            $this->r_collection->missing(['recursive_associate.x' => 'xxx'])
        );
        $this->assertFalse(
            $this->r_collection->missing(['multi_recursive_associate.mmm' => ['x' => 'xxx', 'y' => 'yyy']])
        );
    }

    // DONE
    public function testParametersMissingByValue()
    {
        $this->assertFalse(
            $this->r_collection->missing('bat')
        );
        $this->assertFalse(
            $this->r_collection->missing(null)
        );
        $this->assertFalse(
            $this->r_collection->missing(true)
        );
        $this->assertTrue(
            $this->r_collection->missing('far')
        );
        $this->assertTrue(
            $this->r_collection->missing(false)
        );
        $this->assertTrue(
            $this->r_collection->missing(5)
        );
        $this->assertFalse(
            $this->r_collection->missing('xxx')
        );
        $this->assertTrue(
            $this->r_collection->missing('uuu')
        );
    }

    // DONE
    public function testParametersUnique()
    {
        $collection = (new ArrayBag([1,1,2,3,4,5,5]))
            ->unique();

        $collection2 = (clone $this->r_collection)
                ->set('recursive2', ['aaa', 'zzz']);

        $this->assertSame(
            $collection->values()->all(),
            [1,2,3,4,5]
        );
        $this->assertSame(
            $collection2->unique()->all(),
            $this->r_collection->all()
        );
    }

    // DONE
    public function testParametersFlip()
    {
        $collection = (new ArrayBag([
            'key' => 'value',
            'again' => 'valued',
        ]))
            ->flip();

        $collection2 = (clone $this->r_collection)
            ->delete('null', 'boolean');

        $this->assertSame(
            $collection->all(),
            [
                'value' => 'key',
                'valued' => 'again',
            ]
        );
        $this->assertSame(
            $collection2->flip()->all(),
            [
                'bar' => 'foo',
                'baz' => 'bar',
                'bat' => 'baz',
                'single' => 'dot.dotted',
            ]
        );
    }

    // DONE
    public function testParametersReverse()
    {
        $collection = (clone $this->r_collection)
            ->reverse();

        $this->assertSame(
            $collection->all(),
            [
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'dot.dotted' => 'single',
                'boolean' => true,
                'null' => null,
                'baz' => 'bat',
                'bar' => 'baz',
                'foo' => 'bar',
            ]
        );
    }

    // DONE
    public function testSortParameters()
    {
        $collection = new ArrayBag([8,7,1,4,5,6,2,3,9,10]);

        $collection2 = new ArrayBag([
            'j' => 8,
            'h' => 7,
            'i' => 1,
            'e' => 4,
            'a' => 5,
            'g' => 6,
            'b' => 2,
            'f' => 3,
            'c' => 9,
            'd' => 10
        ]);

        $this->assertSame(
            $collection->sort()->all(),
            [1,2,3,4,5,6,7,8,9,10]
        );
        $this->assertSame(
            $collection->sort('rsort')->all(),
            [10,9,8,7,6,5,4,3,2,1]
        );
        $this->assertSame(
            $collection->sort('uasort', function($a,$b) {
                if ($a==$b) return 0;
                return ($a<$b)?-1:1;
            })->values()->all(),
            [1,2,3,4,5,6,7,8,9,10]
        );
        $this->assertSame(
            $collection->sort('usort', function($a,$b) {
                if ($a==$b) return 0;
                return ($a<$b)?-1:1;
            })->all(),
            [1,2,3,4,5,6,7,8,9,10]
        );
        $this->assertNotSame(
            [1,2,3,4,5,6,7,8,9,10],
            $collection->sort('uasort', function($a,$b) {
                if ($a==$b) return 0;
                return ($a<$b)?-1:1;
            })->all()
        );
        $this->assertSame(
            $collection2->sort()->all(),
            [1,2,3,4,5,6,7,8,9,10]
        );
        $this->assertSame(
            $collection2->sort('asort')->all(),
            [
                'i' => 1,
                'b' => 2,
                'f' => 3,
                'e' => 4,
                'a' => 5,
                'g' => 6,
                'h' => 7,
                'j' => 8,
                'c' => 9,
                'd' => 10
            ]
        );
        $this->assertSame(
            $collection2->sort('ksort')->all(),
            [
                'a' => 5,
                'b' => 2,
                'c' => 9,
                'd' => 10,
                'e' => 4,
                'f' => 3,
                'g' => 6,
                'h' => 7,
                'i' => 1,
                'j' => 8,
            ]
        );
        $this->assertSame(
            $collection2->sort('uksort', function($a,$b) {
                return strtolower($a) <=> strtolower($b);
            })->all(),
            [
                'a' => 5,
                'b' => 2,
                'c' => 9,
                'd' => 10,
                'e' => 4,
                'f' => 3,
                'g' => 6,
                'h' => 7,
                'i' => 1,
                'j' => 8,
            ]
        );
    }

    // DONE
    public function testSortByParameters()
    {
        $collection = new ArrayBag([
            ['name' => 'Desk', 'colors' => ['Black', 'Mahogany']],
            ['name' => 'Chair', 'colors' => ['Black']],
            ['name' => 'Bookcase', 'colors' => ['Red', 'Beige', 'Brown']],
        ]);

        $this->assertSame(
            $collection->sortBy('name')->all(),
            [
                2 => ['name' => 'Bookcase', 'colors' => ['Red', 'Beige', 'Brown']],
                1 =>['name' => 'Chair', 'colors' => ['Black']],
                0 =>['name' => 'Desk', 'colors' => ['Black', 'Mahogany']],
            ]
        );
        $this->assertSame(
            $collection->sortBy('colors')->all(),
            [
                1 => ['name' => 'Chair', 'colors' => ['Black']],
                0 => ['name' => 'Desk', 'colors' => ['Black', 'Mahogany']],
                2 => ['name' => 'Bookcase', 'colors' => ['Red', 'Beige', 'Brown']],
            ]
        );
    }

    // DONE
    public function testShiftParameters()
    {
        $collection = clone($this->r_collection);

        $this->assertSame(
            $collection->shift(), 'bar'
        );
        $this->assertSame(
            $collection->all(),
            [
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection->shift(3),
            [
                'baz',
                'bat',
                 null,
            ]
        );
        $this->assertSame(
            $collection->all(),
            [
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testSkipParameters()
    {
        $collection = clone($this->r_collection);
        $collection2 = new ArrayBag([]);

        $this->assertSame(
            $collection->skip(2)->all(),
            [
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection2->skip(2)->all(),
            []
        );
        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection->skip(function($value, $key) {
                return is_array($value);
            })->all(),
            [
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testSliceParameters()
    {
        $collection2 = new ArrayBag([]);

        $this->assertSame(
            $this->r_collection->slice(4)->all(),
            [
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection2->slice(2)->all(),
            []
        );
        $this->assertSame(
            $this->r_collection->slice(4, 2)->all(),
            [
                'boolean' => true,
                'dot.dotted' => 'single',
            ]
        );
    }

    // DONE
    public function testSpliceParameters()
    {
        $this->assertSame(
            $this->r_collection->splice(4)->all(),
            [
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $this->r_collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
            ]
        );
    }

    // DONE
    public function testParametersSplit()
    {
        $collection = (new ArrayBag([1,2,3,4,5,6,7,8]));

        $this->assertSame(
            $collection->split(3)->all(),
            [
                [1,2,3],
                [4,5,6],
                [7,8],
            ]
        );
        $this->assertSame(
            $collection->split(4)->all(),
            [
                [1,2],
                [3,4],
                [5,6],
                [7,8],
            ]
        );
    }

    // DONE
    public function testParametersChunk()
    {
        $collection = $this->r_collection
            ->chunk(3);

        $this->assertSame(
            $collection->all(),
            [
                [
                    'foo' => 'bar',
                    'bar' => 'baz',
                    'baz' => 'bat',
                ],
                [
                    'null' => null,
                    'boolean' => true,
                    'dot.dotted' => 'single',
                ],
                [
                    'recursive' => [
                        'aaa',
                        'zzz',
                    ],
                    'recursive_associate' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                    'multi_recursive' => [
                        'mmm' => [
                            'aaa',
                            'zzz',
                        ],
                    ],
                ],
                [
                    'multi_recursive_associate' => [
                        'mmm' => [
                            'x' => 'xxx',
                            'y' => 'yyy',
                        ],
                    ],
                ]
            ]
        );
    }

    // DONE
    public function testParametersPage()
    {
        $collection = (new ArrayBag([1,2,3,4,5,6,7,8]))
            ->page(2, 3);

        $collection2 = (new ArrayBag([1,2]))
            ->page(2, 3);

        $collection3 = (new ArrayBag([1,2,3,4,5,6,7,8]))
            ->page(4, 3);

        $this->assertSame(
            $collection->all(),
            [4,5,6]
        );
        $this->assertSame(
            $collection2->all(),
            []
        );
        $this->assertSame(
            $collection3->all(),
            []
        );
    }

    // DONE
    public function testParametersLoop()
    {
        $collection = (new ArrayBag(
            ['object' => clone($this->r_collection), 'rec' => ['modify' => clone($this->r_collection)]]
        ))
            ->loop(function ($value, $key) {
                if($key !== 'object') {
                    $value->set('foo', 'notbaz');
                }
            });

        $this->assertSame(
            $collection->get('rec.modify')->get('foo'), 'notbaz'
        );
        $this->assertNotSame(
            'notbaz', $collection->get('object')->get('foo')
        );
    }

    // DONE
    public function testEveryParametersLoop()
    {
        $this->assertFalse(
            $this->r_collection->every(function ($value, $key) {
                return is_string($value);
            })
        );

        $this->assertTrue(
            $this->r_collection->every(function ($value, $key) {
                return !is_int($value);
            })
        );
    }

    // DONE
    public function testAttemptParameters()
    {
        $this->assertTrue(
            $this->r_collection->attempt(function($v, $k) {
                if($v === 'xxx') {
                    return true;
                }
            })
        );
        $this->assertTrue(
            $this->r_collection->attempt(function($v, $k) {
                if($v === 'aaa' && is_numeric($k)) {
                    return true;
                }
            })
        );
        $this->assertFalse(
            $this->r_collection->attempt(function($v, $k) {
                if($v === 'mmm') {
                    return true;
                }
            })
        );
        $this->assertTrue(
            $this->r_collection->attempt(function($v, $k) {
                if($k === 'y' && $v === 'yyy') {
                    return true;
                }
            })
        );
    }

    // DONE
    public function testParametersTransform()
    {
        $modified = (clone($this->r_collection));

        $modified
            ->transform(function ($value) {
                return is_string($value) ? $value.'K' : $value;
            });

        $this->assertSame(
            $modified->all(),
            [
                'foo' => 'barK',
                'bar' => 'bazK',
                'baz' => 'batK',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'singleK',
                'recursive' => [
                    'aaaK',
                    'zzzK',
                ],
                'recursive_associate' => [
                    'x' => 'xxxK',
                    'y' => 'yyyK',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaaK',
                        'zzzK',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxxK',
                        'y' => 'yyyK',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testParametersMap()
    {
        $collection = (clone($this->r_collection));

        $modified = $collection
            ->map(function ($value) {
                return is_string($value) ? $value.'K' : $value;
            });

        $this->assertSame(
            $modified->all(),
            [
                'foo' => 'barK',
                'bar' => 'bazK',
                'baz' => 'batK',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'singleK',
                'recursive' => [
                    'aaaK',
                    'zzzK',
                ],
                'recursive_associate' => [
                    'x' => 'xxxK',
                    'y' => 'yyyK',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaaK',
                        'zzzK',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxxK',
                        'y' => 'yyyK',
                    ],
                ],
            ]
        );
        $this->assertNotSame(
            [
                'foo' => 'barK',
                'bar' => 'bazK',
                'baz' => 'batK',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'singleK',
                'recursive' => [
                    'aaaK',
                    'zzzK',
                ],
                'recursive_associate' => [
                    'x' => 'xxxK',
                    'y' => 'yyyK',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaaK',
                        'zzzK',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxxK',
                        'y' => 'yyyK',
                    ],
                ],
            ],
            $collection->all()
        );
    }

    // DONE
    public function testParametersMapWithKeys()
    {
        $collection = (clone($this->r_collection))
            ->map(function ($value, $key) {
                return $key === 'foo' ? $value.'K' : $value;
            });

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'barK',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testParametersMapPairs()
    {
        $collection = (clone($this->r_collection))
            ->mapPair(function ($value) {
                return !is_string($value) ?: [$value => 'value'];
            });

        $this->assertSame(
            $collection->all(),
            [
                'bar' => 'value',
                'baz' => 'value',
                'bat' => 'value',
                'single' => 'value',
                'aaa' => 'value',
                'zzz' => 'value',
                'xxx' => 'value',
                'yyy' => 'value',
            ]
        );
    }

    // DONE
    public function testFilterParameters()
    {
        $collection = (clone($this->r_collection))
            ->set('another', false)
            ->filter(fn($v) => !is_null($v) && $v !== false);

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testFilterKeysParameters()
    {
        $collection = (clone($this->r_collection))
            ->set('another', false)
            ->filter(fn($v, $k) => !is_null($v) && $v !== false && $v !== 'aaa' && $k !== 'foo');

        $this->assertSame(
            $collection->all(),
            [
                'bar' => 'baz',
                'baz' => 'bat',
                'boolean' => true,
                'dot.dotted' => 'single',
                'recursive' => [
                    1 => 'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        1 => 'zzz',
                    ],
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testParametersDiffByValue()
    {
        $diff = [
            'bat',
            null,
            true,
            'recursive' => [
                'aaa',
            ],
            'recursive_associate' => [
                'x' => 'xxx',
                'y' => 'zzz',
            ],
        ];

        $collection = (clone($this->r_collection))
            ->diff($diff);

        $this->assertSame(
            $collection->get('foo'), 'bar'
        );
        $this->assertNull(
            $collection->get('baz')
        );
        $this->assertNull(
            $collection->get('boolean')
        );
        $this->assertSame(
            $collection->get('recursive.1'), 'zzz'
        );
        $this->assertNull(
            $collection->get('recursive_associate.x')
        );
        $this->assertSame(
            $collection->get('recursive_associate.y'), 'yyy'
        );
    }

    // DONE
    public function testParametersDiffByKey()
    {
        $diff = [
            'bar',
            'baz',
            'null',
            'boolean',
            'recursive' => [
                0,
            ],
            'recursive_associate' => [
                'm',
                'n',
            ],
            'multi_recursive_associate' => [
                'mmm' => [
                    'x',
                    'y',
                ],
            ],
        ];

        $collection = (clone($this->r_collection))
            ->diffKey($diff);

        $this->assertSame(
            $collection->get('foo'), 'bar'
        );
        $this->assertNull(
            $collection->get('bar')
        );
        $this->assertNull(
            $collection->get('baz')
        );
        $this->assertNull(
            $collection->get('boolean')
        );
        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'dot.dotted' => 'single',
                'recursive' => [
                    1 => 'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testParametersDiffByAssoc()
    {
        $diff = [
            'foo' => 'baz',
            'bac' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
            'multi_recursive' => [
                'xxx' => [
                    'aaa',
                    'zzz',
                ],
            ],
            'multi_recursive_associate' => [
                'mmm' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
            ],
        ];

        $collection = (clone($this->r_collection))
            ->diffAssoc($diff);

        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'dot.dotted' => 'single',
                'recursive' => [
                    'aaa',
                    'zzz',
                ],
                'recursive_associate' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
                'multi_recursive' => [
                    'mmm' => [
                        'aaa',
                        'zzz',
                    ],
                ],
            ]
        );
        $this->assertSame(
            $collection->get('foo'), 'bar'
        );
        $this->assertSame(
            $collection->get('bar'), 'baz'
        );
        $this->assertNull(
            $collection->get('baz')
        );
        $this->assertNull(
            $collection->get('null')
        );
        $this->assertNull(
            $collection->get('boolean')
        );
    }

    // DONE
    public function testParametersIntersectByValue()
    {
        $diff = [
            'bat',
            null,
            true,
            'recursive' => [
                'aaa',
            ],
            'recursive_associate' => [
                'x' => 'xxx',
                'y' => 'zzz',
            ],
        ];

        $collection = (clone($this->r_collection))
            ->intersect($diff);

        $this->assertNull(
            $collection->get('foo')
        );
        $this->assertSame(
            $collection->get('baz'), 'bat'
        );
        $this->assertSame(
            $collection->get('boolean'), true
        );
        $this->assertNull(
            $collection->get('recursive.1')
        );
        $this->assertSame(
            $collection->get('recursive_associate.x'), 'xxx'
        );
        $this->assertNull(
            $collection->get('recursive_associate.y')
        );
    }

    // DONE
    public function testParametersIntersectByKey()
    {
        $diff = [
            'bar',
            'baz',
            'null',
            'boolean',
            'recursive' => [
                0,
            ],
            'recursive_associate' => [
                'm',
                'n',
            ],
            'multi_recursive_associate' => [
                'mmm' => [
                    'x',
                    'y',
                ],
            ],
        ];

        $collection = (clone($this->r_collection))
            ->intersectKey($diff);

        $this->assertNull(
            $collection->get('foo')
        );
        $this->assertSame(
            $collection->get('bar'), 'baz'
        );
        $this->assertSame(
            $collection->get('baz'), 'bat'
        );
        $this->assertNull(
            $collection->get('null')
        );
        $this->assertSame(
            $collection->all(),
            [
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'recursive' => [
                    'aaa',
                ],
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
    }

    // DONE
    public function testParametersIntersectByAssoc()
    {
        $diff = [
            'foo' => 'baz',
            'bac' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
            'multi_recursive' => [
                'xxx' => [
                    'aaa',
                    'zzz',
                ],
            ],
            'multi_recursive_associate' => [
                'mmm' => [
                    'x' => 'xxx',
                    'y' => 'yyy',
                ],
            ],
        ];

        $collection = (clone($this->r_collection))
            ->intersectAssoc($diff);

        $this->assertSame(
            $collection->all(),
            [
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'multi_recursive_associate' => [
                    'mmm' => [
                        'x' => 'xxx',
                        'y' => 'yyy',
                    ],
                ],
            ]
        );
        $this->assertNull(
            $collection->get('foo')
        );
        $this->assertNull(
            $collection->get('bar')
        );
        $this->assertSame(
            $collection->get('baz'), 'bat'
        );
        $this->assertSame(
            $collection->get('null'), null
        );
        $this->assertSame(
            $collection->get('boolean'), true
        );

    }

    // DONE
    public function testReduceParameters()
    {
        $reduce = (new ArrayBag([1,[2,3],4,5]))
            ->reduce(function ($carry, $item) {
                if(is_array($item)) {
                    $item = array_sum($item);
                }
                return $carry + $item;
            });

        $reduce2 = (new ArrayBag([1,2,3,4,5]))
            ->reduce(function ($carry, $item) {
                return $carry + $item;
            }, 5);

        $this->assertSame(
            $reduce, 15
        );

        $this->assertSame(
            $reduce2, 20
        );
    }

    // DONE
    public function testImplodeParameters()
    {
        $collection = (clone($this->r_collection));

        $this->assertSame(
            $collection->implode(', '),
            'bar, baz, bat, single, aaa, zzz, xxx, yyy, aaa, zzz, xxx, yyy'
        );
        $this->assertSame(
            $collection->implode(', ', function($value, $key) {
                return strtoupper($value);
            }),
            'BAR, BAZ, BAT, SINGLE, AAA, ZZZ, XXX, YYY, AAA, ZZZ, XXX, YYY'
        );
        $this->assertSame(
            $collection->implode(', ', function($value, $key) {
                return $key !== 'foo' ? $value : strtoupper($value);
            }),
            'BAR, baz, bat, single, aaa, zzz, xxx, yyy, aaa, zzz, xxx, yyy'
        );
    }

    // DONE
    public function testImplodeByParameters()
    {
        $collection = (clone($this->r_collection));

        $this->assertSame(
            $collection->implodeBy('x', ', '),
            'xxx, xxx'
        );
    }
    
    // DONE
    public function testCollapseParameters()
    {
        $collection = $this->r_collection
            ->collapse();

        $collection2 = $this->r_collection
            ->collapse(null, true);

        $this->assertSame(
            $collection2->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                0 => 'aaa',
                1 => 'zzz',
                'x' => 'xxx',
                'y' => 'yyy',
            ]
        );
        $this->assertSame(
            $collection->all(),
            [
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'bat',
                'null' => null,
                'boolean' => true,
                'dot.dotted' => 'single',
                0 => 'aaa',
                1 => 'zzz',
                'x' => 'xxx',
                'y' => 'yyy',
                2 => 'aaa',
                3 => 'zzz',
                4 => 'xxx',
                5 => 'yyy',
            ]
        );
    }

    // DONE
    public function testCollapseCallbackParameters()
    {
        $collection = (new ArrayBag([[1,2,3],4,[5,[6,7],8]]))
            ->collapse(function ($values) {
                return array_map(fn($v) => $v+1, $values);
            });

        $this->assertSame(
            $collection->all(),
            [2,3,4,5,6,7,8,9]
        );
    }

    // DONE
    public function testFlattenCallbackParameters()
    {
        $collection = $this->r_collection
            ->flatten();

        $this->assertSame(
            $collection->all(),
            [
                0 => 'bar',
                1 => 'baz',
                2 => 'bat',
                3 => null,
                4 => true,
                5 => 'single',
                6 => 'aaa',
                7 => 'zzz',
                8 => 'xxx',
                9 => 'yyy',
                10 => 'aaa',
                11 => 'zzz',
                12 => 'xxx',
                13 => 'yyy',
            ]
        );
    }

    // DONE
    public function testFirstWhereParameters()
    {
        $collection = (new ArrayBag([
            ['name' => 'aa', 'tag' => null],
            ['name' => 'bb', 'tag' => false],
            [['name' => 'cc', 'tag' => 5]],
            ['name' => 'dd', 'tag' => 52],
        ]));

        $collection2 = (new ArrayBag([
            [['name' => 'aa', 'tag' => null]],
            ['name' => 'bb', 'tag' => false],
            [[['name' => 'cc', 'tag' => 5]]],
        ]));

        $this->assertSame(
            $collection->firstWhere('tag'),
            ['name' => 'cc', 'tag' => 5]
        );
        $this->assertSame(
            $collection->firstWhere('name', 'bb'),
            ['name' => 'bb', 'tag' => false]
        );
        $this->assertSame(
            $collection->firstWhere('tag', '>',5),
            ['name' => 'dd', 'tag' => 52]
        );
        $this->assertSame(
            $collection2->firstWhere('name', 'aa'),
            ['name' => 'aa', 'tag' => null]
        );
        $this->assertSame(
            $collection2->firstWhere('name', '=','cc'),
            ['name' => 'cc', 'tag' => 5]
        );
        $this->assertFalse(
            $collection2->firstWhere('name', 'zz')
        );
    }

    // DONE
    public function testLastWhereParameters()
    {
        $collection = (new ArrayBag([
            ['name' => 'aa', 'tag' => null],
            ['name' => 'bb', 'tag' => false],
            [['name' => 'cc', 'tag' => 5]],
            ['name' => 'dd', 'tag' => 52],
            ['name' => 'bb', 'tag' => true],
            ['name' => 'xx', 'tag' => 82],
        ]));

        $collection2 = (new ArrayBag([
            [['name' => 'aa', 'tag' => null]],
            ['name' => 'bb', 'tag' => false],
            [[['name' => 'cc', 'tag' => 5]]],
            [['name' => 'aa', 'tag' => 25]],
        ]));

        $this->assertSame(
            $collection->lastWhere('tag'),
            ['name' => 'xx', 'tag' => 82]
        );
        $this->assertSame(
            $collection->lastWhere('name', 'bb'),
            ['name' => 'bb', 'tag' => true]
        );
        $this->assertSame(
            $collection->lastWhere('tag', '>',5),
            ['name' => 'xx', 'tag' => 82]
        );
        $this->assertSame(
            $collection2->lastWhere('name', 'aa'),
            ['name' => 'aa', 'tag' => 25]
        );
        $this->assertSame(
            $collection2->lastWhere('name', '=','cc'),
            ['name' => 'cc', 'tag' => 5]
        );
        $this->assertFalse(
            $collection2->lastWhere('name', 'zz')
        );
    }

    // DONE
    public function testGatherParameters()
    {
        $collection = new ArrayBag(
            [
                ['id' => '452', 'name' => 'Comment'],
                ['id' => '841', 'name' => 'Relation'],
            ]
        );

        $this->assertSame(
            $collection->gather('name'),
            ['Comment', 'Relation']
        );
        $this->assertSame(
            $collection->gather('name', 'id'),
            ['452' => 'Comment', '841' => 'Relation']
        );
    }

    // DONE
    public function testSeparateParameters()
    {
        $collection = new ArrayBag(
            [
                'aa=aa;bb=bb;cc=cc;dd=dd;ee=ee;ff=ff',
                'gg=gg;hh;ii;jj;kk',
            ]
        );

        $this->assertSame(
            $collection->separate(';')->all(),
            [
                'aa=aa',
                'bb=bb',
                'cc=cc',
                'dd=dd',
                'ee=ee',
                'ff=ff',
                'gg=gg',
                'hh',
                'ii',
                'jj',
                'kk',
            ]
        );
        $this->assertSame(
            $collection->separate(';=')->all(),
            [
                'aa',
                'aa',
                'bb',
                'bb',
                'cc',
                'cc',
                'dd',
                'dd',
                'ee',
                'ee',
                'ff',
                'ff',
                'gg',
                'gg',
                'hh',
                'ii',
                'jj',
                'kk',
            ]
        );
    }

    // DONE
    public function testPairParameters()
    {
        $collection = (new ArrayBag(
            [
                'aa=aa;bb=bb;cc=cc;dd=dd;ee=ee;ff=ff',
                'gg=gg;hh;ii;jj;kk',
            ]
        ))->separate(';');

        $this->assertSame(
            $collection->pair('=')->all(),
            [
                'aa' => 'aa',
                'bb' => 'bb',
                'cc' => 'cc',
                'dd' => 'dd',
                'ee' => 'ee',
                'ff' => 'ff',
                'gg' => 'gg',
                'hh' => true,
                'ii' => true,
                'jj' => true,
                'kk' => true,
            ]
        );
    }
}
