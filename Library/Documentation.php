<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir;

use Zephir\Commands\CommandInterface;
use Zephir\Di\ContainerAwareTrait;
use Zephir\Di\InjectionAwareInterface;
use Zephir\Documentation\File;
use Zephir\Documentation\NamespaceAccessor;
use Zephir\Documentation\Theme;
use Zephir\Exception\InvalidArgumentException;

/**
 * Documentation Generator
 */
class Documentation implements InjectionAwareInterface
{
    use ContainerAwareTrait {
        ContainerAwareTrait::__construct as protected __DiInject;
    }

    protected $outputDirectory;

    /** @var Config */
    protected $config;

    /** @var CompilerFile[] */
    protected $classes;

    /** @var Theme */
    protected $theme;

    /** @var Logger */
    protected $logger;

    /** @var CommandInterface */
    protected $command;

    protected $baseUrl;

    protected $themesDirectories;

    /**
     * Documentation constructor.
     *
     * @param CompilerFile[]   $classes
     * @param Config           $config
     * @param Logger           $logger
     * @param CommandInterface $command
     *
     * @throws ConfigException
     * @throws Exception
     */
    public function __construct(array $classes, Config $config, Logger $logger, CommandInterface $command)
    {
        $this->__DiInject();

        ksort($classes);

        $this->config = $config;
        $this->classes = $classes;
        $this->logger  = $logger;
        $this->command = $command;

        $themeConfig = $config->get("theme", "api");

        if (!$themeConfig) {
            throw new ConfigException("Theme configuration is not present");
        }

        $themeDir = $this->findThemeDirectory($themeConfig, $command);

        if (!file_exists($themeDir)) {
            throw new ConfigException("There is no theme named " . $themeConfig["name"]);
        }

        $outputDir = $this->findOutputDirectory($command);

        if (!$outputDir) {
            throw new ConfigException("Api path (output directory) is not configured");
        }

        $this->outputDirectory = $outputDir;

        if (!file_exists($outputDir)) {
            if (!mkdir($outputDir, 0777, true)) {
                throw new Exception("Can't write output directory $outputDir");
            }
        }

        if (!is_writable($outputDir)) {
            throw new Exception("Can't write output directory $outputDir");
        }

        $themeConfig["options"] = $this->prepareThemeOptions($themeConfig, $command);

        $this->theme = new Theme($themeDir, $outputDir, $themeConfig, $config, $this);

        $this->baseUrl = $this->parseBaseUrl($command);
    }

    /**
     *
     * Prepare the options by merging the one in the project config with the one in the command line arg "theme-options"
     *
     * command line arg "theme-options" can be either a path to a json file containing the options or a raw json string
     *
     * @param $themeConfig
     * @param CommandInterface $command
     * @return array
     * @throws Exception
     */
    private function prepareThemeOptions($themeConfig, CommandInterface $command)
    {
        $optionsFromCommand = $command->getParameter("theme-options");

        $parsedOptions = null;
        if ($optionsFromCommand) {
            if ("{" == $optionsFromCommand{0}) {
                $parsedOptions = json_decode(trim($optionsFromCommand), true);
                if (!$parsedOptions || !is_array($parsedOptions)) {
                    throw new Exception("Unable to parse json from 'theme-options' argument");
                }
            } else {
                if (file_exists($optionsFromCommand)) {
                    $unparsed = file_get_contents($optionsFromCommand);
                    $parsedOptions = json_decode($unparsed, true);
                    if (!$parsedOptions || !is_array($parsedOptions)) {
                        throw new Exception("Unable to parse json from the file '$optionsFromCommand'");
                    }
                } else {
                    throw new Exception("Unable to find file '$optionsFromCommand'");
                }
            }
        }


        if ($parsedOptions) {
            $options = array_merge($themeConfig["options"], $parsedOptions);
        } else {
            $options = $themeConfig["options"];
        }

        return $options;
    }

    /**
     * Find the directory where the doc is going to be generated depending on the command line options and the config.
     *
     * output directory is checked in this order :
     *  => check if the command line argument --output-directory was given
     *  => if not ; check if config config[api][path] was given
     *
     *
     * @param CommandInterface $command
     *
     * @return null|string
     */
    private function findOutputDirectory(CommandInterface $command)
    {
        $outputDir = $command->getParameter("output-directory");

        if (!$outputDir) {
            $outputDir = $this->config->get('path', 'api');
        }

        $outputDir = str_replace('%version%', $this->config->get('version'), $outputDir);

        if ("/" !== $outputDir{0}) {
            $outputDir = getcwd() . "/" . $outputDir;
        }

        return $outputDir;
    }

    /**
     *
     * Find the base url (useful for sitemap.xml) for either
     *
     * - the command line argument base-url
     * - or the config config["api"]["base-url"]
     *
     * @param  CommandInterface $command
     * @return mixed|string
     */
    private function parseBaseUrl(CommandInterface $command)
    {
        $baseUrl = $command->getParameter("base-url");

        if (!$baseUrl) {
            $baseUrl = $this->config->get("base-url", "api");
        }

        return $baseUrl;
    }

    /**
     * Find the theme directory depending on the command  line options and the config.
     *
     * theme directory is checked in this order :
     *  => check if the command line argument --theme-path was given
     *  => if not ; find the different theme directories on the config $config['api']['theme-directories']
     *  search the theme from the name ($config['api']['theme']['name'] in the theme directories,
     * if nothing was found, we look in the zephir install dir default themes (templates/Api/themes)
     *
     * @param  array            $themeConfig
     * @param  CommandInterface $command
     * @return null|string
     *
     * @throws InvalidArgumentException
     * @throws ConfigException
     */
    private function findThemeDirectory($themeConfig, CommandInterface $command)
    {
        // check if there are additional theme paths in the config
        $themeDirectoriesConfig = $this->config->get("theme-directories", "api");
        if ($themeDirectoriesConfig) {
            if (is_array($themeDirectoriesConfig)) {
                $themesDirectories = $themeDirectoriesConfig;
            } else {
                throw new InvalidArgumentException("Invalid value for theme config 'theme-directories'");
            }
        } else {
            $themesDirectories = [];
        }

        /** @var Environment $environment */
        $environment = $this->container->get(Environment::class);

        $themesDirectories[] = $environment->getPath('templates/Api/themes');
        $this->themesDirectories = $themesDirectories;

        // check if the path was set from the command
        $themePath = $command->getParameter("theme-path");
        if (null !== $themePath) {
            echo $themePath, PHP_EOL;
            if (file_exists($themePath) && is_dir($themePath)) {
                return $themePath;
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        "Invalid value for option 'theme-path': the theme '%s' was not found or is not a valid theme.",
                        $themePath
                    )
                );
            }
        }

        if (!isset($themeConfig["name"]) || !$themeConfig["name"]) {
            throw new ConfigException(
                'There is no theme neither in the the theme config nor as a command line argument'
            );
        }

        return $this->findThemePathByName($themeConfig["name"]);
    }

    /**
     * Search a theme by its name.
     *
     * Return the path to it if it exists. Otherwise NULL.
     *
     * @param  string $name
     * @return string|null
     */
    public function findThemePathByName($name)
    {
        // check the theme from the config
        $path = null;

        foreach ($this->themesDirectories as $themeDir) {
            $path = rtrim($themeDir, '\\/') . DIRECTORY_SEPARATOR . $name;
            if (strpos($path, 'phar://') !== 0) {
                $path = realpath($path);
            }

            if (is_dir($path)) {
                break;
            }
        }

        return $path;
    }

    public function build()
    {
        foreach ($this->classes as $class) {
            // class files (class/ns1/n2/class.html)
            $cfile = new File\ClassFile($this->config, $class);
            $this->theme->drawFile($cfile);

            // class source file (source/ns1/n2/class.html)
            $sfile = new File\SourceFile($this->config, $class);
            $this->theme->drawFile($sfile);
        }

        // classes file (classes.html)
        $file = new File\ClassesFile($this->config, $this->classes);
        $this->theme->drawFile($file);


        $namespaceAccessor = new NamespaceAccessor($this->classes);
        $namespaceAccessor->build();

        $byNamespace = $namespaceAccessor->getByNamespace();

        foreach ($byNamespace as $namespaceName => $nh) {
            // namespace files (namespace/ns1/n2/namespace.html)
            $nfile = new File\NamespaceFile($this->config, $nh);
            $this->theme->drawFile($nfile);
        }


        /** @var Environment $environment */
        $environment = $this->container->get(Environment::class);

        $sitemapFile = new File\Sitemap($environment->getPath(), $this->baseUrl, $this->classes, $byNamespace);
        $this->theme->drawFile($sitemapFile);

        // namespaces files (namespaces.html)
        $nsfile = new File\NamespacesFile($this->config, $namespaceAccessor);
        $this->theme->drawFile($nsfile);


        // index (index.html)
        $indexfile = new File\IndexFile($this->config, $namespaceAccessor);
        $this->theme->drawFile($indexfile);

        $this->theme->buildStaticDirectory();

        $JsonClassDef = $this->theme->buildJsonClassDefinition($this->classes, $namespaceAccessor);
        $this->theme->createFile("asset/api_definition.js", "var ZephirApi = $JsonClassDef;");
    }

    public static function classUrl($c)
    {
        if ($c instanceof ClassDefinition) {
            $cname = $c->getCompleteName();
        } else {
            $cname = $c;
        }

        return "/class/" . str_replace("\\", "/", $cname) . ".html";
    }

    public static function namespaceUrl($ns)
    {
        return "/namespace/" . str_replace("\\", "/", $ns) . ".html";
    }

    public static function sourceUrl(ClassDefinition $c)
    {
        return "/source/" . str_replace("\\", "/", $c->getCompleteName()) . ".html";
    }

    /**
     * get the directory where the doc is going to be generated
     * @return string
     */
    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }
}
