<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Commands;

use Zephir\Config;
use Zephir\Logger;
use Zephir\Exception\InvalidArgumentException;

/**
 * Zephir\Commands\CommandApi
 *
 * Generates a HTML API based on the classes exposed in the extension.
 *
 * @package Zephir\Commands
 */
class CommandApi extends CommandAbstract
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCommand()
    {
        return 'api';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Generates a HTML API based on the classes exposed in the extension';
    }

    /**
     * {@inheritdoc}
     *
     * @param Config $config
     * @param Logger $logger
     * @throws InvalidArgumentException
     */
    public function execute(Config $config, Logger $logger)
    {
        if ($this->hasHelpOption()) {
            echo $this->getSynopsis();
            return;
        }

        $params = $this->parseArguments();

        $allowedArgs = [
            "theme-path"       => "@.+@",
            "output-directory" => "@.+@",
            "theme-options"    => "@.+@",
            "base-url"         => "@.+@",
        ];

        foreach ($params as $k => $p) {
            if (!isset($allowedArgs[$k])) {
                throw new InvalidArgumentException("Invalid argument '$k' for api command'");
            }

            if (!preg_match($allowedArgs[$k], $p)) {
                throw new InvalidArgumentException("Invalid value for argument '$k'");
            }

            $this->setParameter($k, $p);
        }

        parent::execute($config, $logger);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getUsage()
    {
        $template =<<<EOL
%s [options]

Description:
    The options are as follows:

    --theme-path=/path               The API theme to be used.

    --output-directory=/path         Output directory to generate theme.

    --theme-options={json}|/path     Theme options.
EOL;

        return sprintf($template, $this->getCommand());
    }
}
