<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!extension_loaded('test')) {
    if (ini_get('enable_dl') == '1') {
        $prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
        dl($prefix . 'test.' . PHP_SHLIB_SUFFIX);
    } else {
        exit('"test" extension not loaded; cannot run tests without it');
    }
}

$total = 0;

function start_test()
{
    ob_start();
    return microtime(true);
}

function end_test($start, $name, $overhead = null)
{
    global $total;
    global $last_time;
    $end = microtime(true);
    ob_end_clean();
    $last_time = $end-$start;
    $total += $last_time;
    $num = number_format($last_time, 3);
    $pad = str_repeat(" ", 24-strlen($name)-strlen($num));
    if (is_null($overhead)) {
        echo $name.$pad.$num."\n";
    } else {
        $num2 = number_format($last_time - $overhead, 3);
        echo $name.$pad.$num."    ".$num2."\n";
    }
    ob_start();
    return microtime(true);
}

function total()
{
    global $total;
    $pad = str_repeat("-", 24);
    echo $pad."\n";
    $num = number_format($total, 3);
    $pad = str_repeat(" ", 24-strlen("Total")-strlen($num));
    echo "Total".$pad.$num."\n";
}

const N = 5000000;

echo "Benchmark Zephir ". (new \Zephir\Version())->get() . " \n";

$x = new \Test\Bench\Foo();

$t = start_test();
$x->emptyForInRange(N);
$t = end_test($t, 'empty for in range');

$x->readProp(N);
$t = end_test($t, '$x = $this->x');

$x->writeProp(N);
$t = end_test($t, '$this->x = 0');

$x->writeStatic(N);
$t = end_test($t, 'self::a = 0');

$x->assignAddProp(N);
$t = end_test($t, '$this->x += 2');

//$x->pre_inc_prop(N);
//$t = end_test($t, '++$this->x');
//$x->pre_dec_prop(N);
//$t = end_test($t, '--$this->x');

$x->postIncProp(N);
$t = end_test($t, '$this->x++');

$x->postDecProp(N);
$t = end_test($t, '$this->x--');

$x->issetProp(N);
$t = end_test($t, 'isset($this->x)');

$x->issetStatic(N);
$t = end_test($t, 'isset(self::A)');

$x->emptyProp(N);
$t = end_test($t, 'empty($this->x)');

$x->emptyStatic(N);
$t = end_test($t, 'empty(self::a)');

$x->call(N);
$t = end_test($t, '$this->f()');

$x->scall(N);
$t = end_test($t, 'self::f()');

$x->scallWithReturnTrue(N);
$t = end_test($t, 'self::f() -> true');

$x->readConst(N);
$t = end_test($t, '$x = Foo::TEST');
total();
