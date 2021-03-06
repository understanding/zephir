<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Code\Builder;

/**
 * Zephir\Code\Builder\Struct
 *
 * Represents an internal extension global structure
 *
 * @package Code\Builder
 */
class Struct
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $simpleName;

    /** @var array */
    protected $properties = [];

    /**
     * @param string $name
     * @param string $simpleName
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $simpleName)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Struct name must be string');
        }

        if (!is_string($simpleName)) {
            throw new \InvalidArgumentException('Struct name must be string');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Struct name must not be empty');
        }

        $this->name = $name;
        $this->simpleName = $simpleName;
    }

    /**
     * @param string $field
     * @param array $global
     * @throws \InvalidArgumentException
     */
    public function addProperty($field, $global)
    {
        if (!isset($global['type'])) {
            throw new \InvalidArgumentException('Property type must be string');
        }

        if (!is_string($global['type'])) {
            throw new \InvalidArgumentException('Property type must be string');
        }

        if (!is_string($field)) {
            throw new \InvalidArgumentException('Property name must be string');
        }

        if (isset($this->properties[$field])) {
            throw new \InvalidArgumentException('Property was defined more than once');
        }

        $this->properties[$field] = $this->convertToCType($global['type']);
    }

    /**
     * Generates the internal c-type according to the php's type
     *
     * @param string $type
     * @return string
     * @throws \Exception
     */
    protected function convertToCType($type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return 'zend_bool';

            case 'hash':
                return 'HashTable* ';

            case 'int':
            case 'uint':
            case 'long':
            case 'char':
            case 'uchar':
            case 'double':
                return $type;

            default:
                throw new \Exception('Unknown global type: ' . $type);
        }
    }

    /**
     * Returns the C code that initializes the extension global
     *
     * @param string $name
     * @param array $global
     */
    public function getCDefault($name, $global, $namespace)
    {
        if (!isset($global['default'])) {
            throw new \Exception('Field "' . $name . '" does not have a default value');
        }

        switch ($global['type']) {
            case 'boolean':
            case 'bool':
                return '';
                /*
                if ($global['default'] === true) {
                    return "\t" . $namespace . '_globals->' . $this->simpleName . '.' . $name . ' = 1;';
                } else {
                    if ($global['default'] === false) {
                        return "\t" . $namespace . '_globals->' . $this->simpleName . '.' . $name . ' = 0;';
                    } else {
                        throw new \Exception('Invalid default type for boolean field "' . $name . '", it must be false/true');
                    }
                }
                */
                break;

            case 'int':
            case 'uint':
            case 'long':
            case 'double':
            case 'hash':
                return "\t" . $namespace . '_globals->' . $this->simpleName . '.' . $name . ' = ' . $global['default'] . ';';

            default:
                throw new \Exception('Unknown global type: ' . $global['type']);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $code = 'typedef struct '. $this->name .' { '.PHP_EOL;

        foreach ($this->properties as $name => $type) {
            $code .= sprintf("\t%s %s;%s", $type, $name, PHP_EOL);
        }

        return $code . '} ' . substr($this->name, 1) . ';' . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getInitEntry($name, $global, $namespace)
    {
        $iniEntry = array();
        if (isset($global['ini-entry'])) {
            $iniEntry = $global['ini-entry'];
        }
        $structName = $this->simpleName . '.' . $name;
        if (!isset($iniEntry['name'])) {
            $iniName = $namespace . '.' . $structName;
        } else {
            $iniName = $iniEntry['name'];
        }
        if (!isset($iniEntry['scope'])) {
            $scope = 'PHP_INI_ALL';
        } else {
            $scope = $iniEntry['scope'];
        }

        switch ($global['type']) {
            case 'boolean':
            case 'bool':
                return
                    'STD_PHP_INI_BOOLEAN("' .
                    $iniName .
                    '", "' .
                    (int) ($global['default'] === true) .
                    '", ' .
                    $scope .
                    ', OnUpdateBool, ' .
                    $structName .
                    ', zend_' .
                    $namespace .
                    '_globals, ' .
                    $namespace . '_globals)';
            break;
        }
        return '';
    }
}
