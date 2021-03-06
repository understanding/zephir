<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Test;

use Zephir\Support\TestCase;
use Zephir\ConfigException;

class ConfigExceptionTest extends TestCase
{
    public function testConstruct()
    {
        $configException = new ConfigException('foo', 25);
        $this->assertSame($configException->getCode(), 25);
    }
}
