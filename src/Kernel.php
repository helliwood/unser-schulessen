<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel implements CompilerPassInterface
{

    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * Kernel constructor.
     * @param $environment
     * @param $debug
     */
    public function __construct(string $environment, bool $debug)
    {
        \date_default_timezone_set('Europe/Berlin');
        parent::__construct($environment, $debug);
    }

    /**
     * @return string[]
     */
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir() . '/config';

        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
    }

    /**
     * @param RouteCollectionBuilder $routes
     * @throws \Symfony\Component\Config\Exception\LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}/*' . self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir . '/{routes}' . self::CONFIG_EXTS, '/', 'glob');
    }

    public function process(ContainerBuilder $container): void
    {
        $dir = $_ENV['APP_STATE_COUNTRY'];
        if (! empty($dir) && \is_dir($this->getProjectDir() . "/translations/" . $dir)) {
            $def = $container->getDefinition('translator.default');
            $arg = $def->getArgument(4);
            \array_splice(
                $arg["scanned_directories"],
                \array_search(
                    $this->getProjectDir() . "/translations/default",
                    $arg["scanned_directories"]
                ) + 1,
                0,
                $this->getProjectDir() . "/translations/" .
                $dir
            );
            \array_splice(
                $arg["cache_vary"]["scanned_directories"],
                \array_search(
                    "translations/default",
                    $arg["cache_vary"]["scanned_directories"]
                ) + 1,
                0,
                "translations/" . $dir
            );

            foreach (\array_keys($arg["resource_files"]) as $lang) {
                $finder = Finder::create()
                    ->files()
                    ->in($this->getProjectDir() . "/translations/" . $dir)
                    ->name('*.' . $lang . '.yaml');
                foreach ($finder as $file) {
                    $arg["resource_files"][$lang][] = $file->getRealPath();
                }
            }
            $arg["resource_files"]["de"][] = $this->getProjectDir() . "/translations/" . $dir . "/messages.de.yaml";
            $def->replaceArgument(4, $arg);
        }
    }
}
