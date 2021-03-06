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
use Zephir\Version;

/**
 * Zephir\Commands\CommandHelp
 *
 * Shows Zephir help and exit
 *
 * @package Zephir\Commands
 */
class CommandHelp extends CommandAbstract
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCommand()
    {
        return 'help';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getUsage()
    {
        return $this->getCommand();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Displays this help and exit';
    }

    /**
     * {@inheritdoc}
     *
     * @param Config $config
     * @param Logger $logger
     * @return int
     */
    public function execute(Config $config, Logger $logger)
    {
        if ($this->hasHelpOption()) {
            return fprintf(STDIN, $this->getSynopsis());
        }

        $version = $this->getContainer()->get(Version::class);

        return fprintf(
            STDOUT,
            "%s\nZephir version %s\n\n%s\nAvailable commands:\n%s\n%s",
            $this->banner(),
            $version,
            $this->usage(),
            $this->commands(),
            $this->options()
        );
    }

    /**
     * Gets available commands.
     *
     * @return string
     */
    private function commands()
    {
        $template = '';

        $commands = $this->getCommandsManager();
        $commands->rewind();

        while ($commands->valid()) {
            $command = $commands->current();
            $template .= sprintf("        %-20s%s\n", $command->getCommand(), $command->getDescription());

            $commands->next();
        }

        return $template;
    }

    /**
     * Gets commands usage.
     *
     * @return string
     */
    private function usage()
    {
        $template =<<<EOL
Usage:
        command [options]

EOL;

        return $template;
    }

    /**
     * Gets Zephir banner.
     *
     * @return string
     */
    private function banner()
    {
        $template =<<<EOL
 _____              __    _
/__  /  ___  ____  / /_  (_)____
  / /  / _ \/ __ \/ __ \/ / ___/
 / /__/  __/ /_/ / / / / / /
/____/\___/ .___/_/ /_/_/_/
         /_/

EOL;

        return $template;
    }

    /**
     * Gets commands options.
     *
     * @return string
     */
    private function options()
    {
        $template =<<<EOL
Options:
        --help|-h           Displays command help and exit
        -f([a-z0-9\-]+)     Enables compiler optimizations
        -fno-([a-z0-9\-]+)  Disables compiler optimizations
        -w([a-z0-9\-]+)     Turns a warning on
        -W([a-z0-9\-]+)     Turns a warning off

EOL;

        return sprintf($template);
    }
}
