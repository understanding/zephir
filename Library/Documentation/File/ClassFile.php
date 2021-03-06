<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Documentation\File;

use Zephir\ClassDefinition;
use Zephir\Documentation\AbstractFile;
use Zephir\CompilerFile;

class ClassFile extends AbstractFile
{
    /**
     * @var ClassDefinition
     */
    protected $class;

    /**
     *
     * @var CompilerFile
     */
    protected $compilerFile;

    public function __construct($config, CompilerFile $class)
    {
        $this->compilerFile = $class;
        $this->class = $class->getClassDefinition();
    }

    public function getTemplateName()
    {
        return "class.phtml";
    }

    public function getData()
    {
        $nsPieces = explode('\\', $this->class->getNamespace());

        $nsPatches = array();
        $nsStr = "";

        foreach ($nsPieces as $n) {
            if (strlen($nsStr) > 0) {
                $nsStr.= '\\';
            }
            $nsStr.= $n;
            $nsPatches[$n] = $nsStr;
        }

        return array(

            "classDefinition" => $this->class,
            "compilerFile"    => $this->compilerFile,
            "className" => $this->class->getName(),
            "classNamespace" => $this->class->getNamespace(),
            "fullName"  => $this->class->getCompleteName(),
            "methods"   => $this->class->getMethods(),
            "namespacePieces" => $nsPatches

        );
    }

    public function getOutputFile()
    {
        return \Zephir\Documentation::classUrl($this->class);
    }
}
