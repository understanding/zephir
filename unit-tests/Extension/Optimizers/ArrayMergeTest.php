<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Extension\Optimizers;

use Test\Optimizers\ArrayMerge;
use Zephir\Support\TestCase;

class ArrayMergeTest extends TestCase
{
    public function testTwoArrays()
    {
        $this->assertSame([1, 2, 3, 4, 5], ArrayMerge::mergeTwoRequiredArrays([1, 2, 3], [4, 5]));
        $this->assertSame([1, 2, 3], ArrayMerge::mergeTwoRequiredArrays([1, 2, 3], []));
        $this->assertSame([1, 2, 3], ArrayMerge::mergeTwoRequiredArrays([], [1, 2, 3]));
    }
}
