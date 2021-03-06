<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Extension\Oo;

use PDO;
use Test\Integration\Psr\Http\Server\MiddlewareInterfaceEx;
use Test\Oo\ConcreteStatic;
use Test\Oo\ExtendPdoClass;
use Zephir\Support\TestCase;

class ExtendClassTest extends TestCase
{
    public function testPDOExtending()
    {
        if (!extension_loaded('pdo')) {
            $this->markTestSkipped('The PDO extendsion is not loaded');
        }
        $this->assertSame(PDO::getAvailableDrivers(), ExtendPdoClass::getAvailableDrivers());
        $this->assertSame(PDO::PARAM_STR, ExtendPdoClass::PARAM_STR);
    }

    public function testPDOStatementExtending()
    {
        $pdo = new ExtendPdoClass('sqlite::memory:', '', '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $stmt = $pdo->prepare('SELECT CURRENT_TIME');

        $this->assertInstanceof('Test\\PdoStatement', $stmt);
    }

    /**
     * @test
     * @issue https://github.com/phalcon/zephir/issues/1686
     */
    public function shouldExtendMiddlewareInterface()
    {
        if (!extension_loaded('psr')) {
            $this->markTestSkipped(
                "The psr extension is not loaded"
            );
        }

        if (!class_exists(MiddlewareInterfaceEx::class)) {
            $this->markTestSkipped(
                sprintf(
                    "You need to compile test extension with %s to perform this test",
                    MiddlewareInterfaceEx::class
                )
            );
        }


        $this->assertTrue(
            is_subclass_of(MiddlewareInterfaceEx::class, 'Psr\Http\Server\MiddlewareInterface')
        );
    }

    // FIXME
    public function testInstanceOfPhalconMvcApplication()
    {
        /*if (!extension_loaded('phalcon')) {
            $this->markTestSkipped('No phalcon ext loaded');
        }
        $class = new \Test\Oo\Extend\Application();
        $this->assertInstanceOf('Phalcon\Mvc\Application', $class);*/
    }

    // FIXME
    public function testInstanceOfMemcache()
    {
        /*if (!extension_loaded('memcache')) {
            $this->markTestSkipped('No memcache ext loaded');
        }
        $class = new \Test\Oo\Extend\Memcache();
        $this->assertInstanceOf('Memcache', $class);*/
    }

    /**
     * @test
     * @issue https://github.com/phalcon/zephir/issues/1392
     */
    public function shouldCorrectWorkWithLateStaticBinding()
    {
        $this->assertSame('Test\Oo\ConcreteStatic', ConcreteStatic::getCalledClass());
    }

    /**
     * @test
     * @issue https://github.com/phalcon/zephir/issues/1372
     */
    public function shouldCallParentMethodFromStaticByUsingSelf()
    {
        $this->assertSame('ConcreteStatic:parentFunction', ConcreteStatic::parentFunction());
        $this->assertSame('ConcreteStatic:parentFunction', ConcreteStatic::childFunction());
    }
}
