<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Detectors;

use Zephir\Variable;

/**
 * ReadDetector
 *
 * Detects if a variable is used in a given expression context
 * Since zvals are collected between executions to the same section of code
 * We need to ensure that a variable is not contained in the "right-side" expression
 * used to assign the variable, avoiding premature initializations
 */
class ReadDetector
{
    public function detect($variable, array $expression)
    {
        if (!isset($expression['type'])) {
            return false;
        }

        /* Remove branch from variable name */
        $pos = strpos($variable, Variable::BRANCH_MAGIC);
        if ($pos > -1) {
            $variable = substr($variable, 0, $pos);
        }

        if ($expression['type'] == 'variable') {
            if ($variable == $expression['value']) {
                return true;
            }
        }

        if ($expression['type'] == 'fcall' || $expression['type'] == 'mcall' || $expression['type'] == 'scall') {
            if (isset($expression['parameters'])) {
                foreach ($expression['parameters'] as $parameter) {
                    if (is_array($parameter['parameter'])) {
                        if ($parameter['parameter']['type'] == 'variable') {
                            if ($variable == $parameter['parameter']['value']) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        if (isset($expression['left'])) {
            if (is_array($expression['left'])) {
                if ($this->detect($variable, $expression['left']) === true) {
                    return true;
                }
            }
        }

        if (isset($expression['right'])) {
            if (is_array($expression['right'])) {
                if ($this->detect($variable, $expression['right']) === true) {
                    return true;
                }
            }
        }

        return false;
    }
}
