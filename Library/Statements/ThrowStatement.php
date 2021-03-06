<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Statements;

use Zephir\CodePrinter;
use Zephir\CompilationContext;
use Zephir\Compiler;
use Zephir\Compiler\CompilerException;
use Zephir\Expression;
use function Zephir\add_slashes;
use function Zephir\fqcn;

/**
 * ThrowStatement
 *
 * Throws exceptions
 */
class ThrowStatement extends StatementAbstract
{
    /**
     * @param CompilationContext $compilationContext
     * @throws CompilerException
     */
    public function compile(CompilationContext $compilationContext)
    {
        $compilationContext->headersManager->add('kernel/exception');

        $codePrinter = $compilationContext->codePrinter;
        $statement = $this->statement;
        $expr = $statement['expr'];

        /**
         * This optimizes throw new Exception("hello")
         */
        if (!$compilationContext->insideTryCatch) {
            if (isset($expr['class']) &&
                isset($expr['parameters']) &&
                count($expr['parameters']) == 1 &&
                $expr['parameters'][0]['parameter']['type'] == 'string'
            ) {
                $className = fqcn(
                    $expr['class'],
                    $compilationContext->classDefinition->getNamespace(),
                    $compilationContext->aliasManager
                );

                if ($compilationContext->compiler->isClass($className)) {
                    $classDefinition = $compilationContext->compiler->getClassDefinition($className);
                    $message = $expr['parameters'][0]['parameter']['value'];
                    $class = $classDefinition->getClassEntry($compilationContext);
                    $this->throwStringException($codePrinter, $class, $message, $statement['expr']);
                    return;
                } else {
                    if ($compilationContext->compiler->isBundledClass($className)) {
                        $classEntry = $compilationContext->classDefinition->getClassEntryByClassName(
                            $className,
                            $compilationContext,
                            true
                        );
                        if ($classEntry) {
                            $message = $expr['parameters'][0]['parameter']['value'];
                            $this->throwStringException($codePrinter, $classEntry, $message, $statement['expr']);
                            return;
                        }
                    }
                }
            } else {
                if (in_array($expr['type'], array('string', 'char', 'int', 'double'))) {
                    $class = $compilationContext->classDefinition->getClassEntryByClassName('Exception', $compilationContext);
                    $this->throwStringException($codePrinter, $class, $expr['value'], $expr);
                    return;
                }
            }
        }

        $throwExpr = new Expression($expr);
        $resolvedExpr = $throwExpr->compile($compilationContext);

        if (!in_array($resolvedExpr->getType(), array('variable', 'string'))) {
            throw new CompilerException("Expression '" . $resolvedExpr->getType() . '" cannot be used as exception', $expr);
        }

        $variableVariable = $compilationContext->symbolTable->getVariableForRead($resolvedExpr->getCode(), $compilationContext, $expr);
        if (!in_array($variableVariable->getType(), array('variable', 'string'))) {
            throw new CompilerException("Variable '" . $variableVariable->getType() . "' cannot be used as exception", $expr);
        }

        $variableCode = $compilationContext->backend->getVariableCode($variableVariable);
        $codePrinter->output('zephir_throw_exception_debug(' . $variableCode . ', "' . Compiler::getShortUserPath($statement['expr']['file']) . '", ' . $statement['expr']['line'] . ' TSRMLS_CC);');
        if (!$compilationContext->insideTryCatch) {
            $codePrinter->output('ZEPHIR_MM_RESTORE();');
            $codePrinter->output('return;');
        } else {
            $codePrinter->output('goto try_end_' . $compilationContext->currentTryCatch . ';');
            $codePrinter->outputBlankLine();
        }

        if ($variableVariable->isTemporal()) {
            $variableVariable->setIdle(true);
        }
    }

    /**
     * Throws an exception escaping the data
     *
     * @param CodePrinter $printer
     * @param string $class
     * @param string $message
     * @param array $expression
     */
    private function throwStringException(CodePrinter $printer, $class, $message, $expression)
    {
        $message = add_slashes($message);
        $path = Compiler::getShortUserPath($expression['file']);
        $printer->output(
            sprintf(
                'ZEPHIR_THROW_EXCEPTION_DEBUG_STR(%s, "%s", "%s", %s);',
                $class,
                $message,
                $path,
                $expression['line']
            )
        );
        $printer->output('return;');
    }
}
