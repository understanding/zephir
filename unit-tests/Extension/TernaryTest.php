<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Extension;

use Zephir\Support\TestCase;

class TernaryTest extends TestCase
{
    public function testTernary()
    {
        $t = new \Test\Ternary();
        $this->assertSame(101, $t->testTernary1());
        $this->assertSame('foo', $t->testTernary2(true));
        $this->assertSame('bar', $t->testTernary2(false));

        $this->assertSame(3, $t->testTernaryAfterLetVariable());
        $this->assertSame(array('', 'c', ''), $t->testTernaryWithPromotedTemporaryVariable());

        $this->assertSame(true, $t->testShortTernary(true));
        $this->assertSame(false, $t->testShortTernary(array()));
        $this->assertSame(array(1,2,3), $t->testShortTernary(array(1,2,3)));
        $this->assertSame(false, $t->testShortTernary(false));
        $this->assertSame(false, $t->testShortTernary(0));

        $this->assertSame(1, $t->testShortTernaryComplex(false, 1));
        $this->assertSame("test string", $t->testShortTernaryComplex(false, "test string"));
        $this->assertSame(array(), $t->testShortTernaryComplex(false, array()));
    }

    public function testComplex()
    {
        $t = new \Test\Ternary();
        $this->assertSame(101, $t->testTernaryComplex1(array(), ""));
        $this->assertSame(106, $t->testTernaryComplex2(array(), ""));
        $this->assertSame("boolean", $t->testTernaryComplex3(""));
    }
}
