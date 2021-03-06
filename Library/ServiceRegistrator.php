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

use Psr\Container\ContainerInterface;
use Zephir\Di\ContainerAwareTrait;
use Zephir\Di\InjectionAwareInterface;
use Zephir\Di\ServiceProviderInterface;
use Zephir\Exception\InvalidArgumentException;
use Zephir\Providers\BackendProvider;
use Zephir\Providers\CommandsManagerProvider;
use Zephir\Providers\ConfigProvider;
use Zephir\Providers\ParserManagerProvider;

/**
 * Zephir\ServiceRegistrator
 *
 * @package Zephir
 */
final class ServiceRegistrator implements InjectionAwareInterface
{
    use ContainerAwareTrait {
        ContainerAwareTrait::__construct as protected __DiInject;
    }

    /**
     * The base path for the application.
     *
     * @var string
     */
    protected $basePath;

    /**
     * A list of the registered service providers.
     *
     * @var ServiceProviderInterface[]
     */
    public $serviceProviders = [];

    /**
     * ServiceRegistrator constructor.
     *
     * @param string|null             $basePath
     * @param ContainerInterface|null $container
     */
    public function __construct($basePath, ContainerInterface $container = null)
    {
        $this->__DiInject($container);

        $this->registerBaseBindings();
        $this->createEnvironment($basePath);
        $this->registerBaseServices();
    }

    /**
     * Registers the base bindings.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        $container = $this->getContainer();

        $container->share(Version::class, Version::class);
        $container->share(Parser::class, Parser::class);
        $container->share(CommandArgumentParser::class, CommandArgumentParser::class);

        $container->share(Logger::class, function () use ($container) {
            return new Logger($container->get(Config::class));
        });
    }

    /**
     * Registers the base services.
     *
     * @return void
     */
    protected function registerBaseServices()
    {
        $this->registerService(new ConfigProvider());
        $this->registerService(new ParserManagerProvider());
        $this->registerService(new BackendProvider());
        $this->registerService(new CommandsManagerProvider());
    }

    /**
     * Creates and registers application environment.
     *
     * @param  string|null $basePath
     * @return void
     */
    protected function createEnvironment($basePath)
    {
        $this->container->share('environment', new Environment($basePath, $this->getContainer()));
    }

    /**
     * Registers a Service Provider in the internal stack.
     *
     * @param  ServiceProviderInterface|string $provider
     * @param  bool                            $force
     * @return ServiceProviderInterface
     *
     * @throws InvalidArgumentException
     */
    public function registerService($provider, $force = false)
    {
        $registered = $this->getServiceProviderByInstanceOf($provider);

        if ($registered && $force == false) {
            // Do not register twice
            return $registered;
        }

        if (is_string($provider)) {
            $provider = $this->instantiateProvider($provider);
        }

        $provider->register($this->getContainer());
        $this->serviceProviders[] = $provider;

        return $provider;
    }

    /**
     * Gets a Service Provider matched by provided instance (if any).
     *
     * @param  ServiceProviderInterface|string $serviceProvider
     * @return ServiceProviderInterface|null
     *
     * @throws InvalidArgumentException
     */
    public function getServiceProviderByInstanceOf($serviceProvider)
    {
        $providers = $this->getServiceProvidersByInstanceOf($serviceProvider);

        return count($providers) ? $providers[0] : null;
    }

    /**
     * Gets Service Providers matched by provided instance.
     *
     * @param  ServiceProviderInterface|string $serviceProvider
     * @return ServiceProviderInterface[]
     *
     * @throws InvalidArgumentException
     */
    public function getServiceProvidersByInstanceOf($serviceProvider)
    {
        $this->assertIsStringOrInstanceOfObject($serviceProvider);

        $className = is_string($serviceProvider) ? $serviceProvider : get_class($serviceProvider);

        return array_filter($this->serviceProviders, function ($name) use ($className) {
            return $name instanceof $className;
        });
    }

    /**
     * Instantiate a service provider.
     *
     * @param  string $serviceProvider
     * @return object|ServiceProviderInterface
     */
    public function instantiateProvider($serviceProvider)
    {
        return new $serviceProvider();
    }

    /**
     * Validates $serviceProvider parameter.
     *
     * @param  string|object $serviceProvider
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function assertIsStringOrInstanceOfObject($serviceProvider)
    {
        if (is_string($serviceProvider) == false && is_object($serviceProvider) == false) {
            throw new InvalidArgumentException(
                sprintf(
                    'The $serviceProvider parameter must be either a string or an object instance, got %s',
                    gettype($serviceProvider)
                )
            );
        }
    }
}
