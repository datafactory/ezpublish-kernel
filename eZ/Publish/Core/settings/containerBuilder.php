<?php

/**
 * File generates service container builder instance.
 *
 * Expects global $installDir to be set by caller
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use eZ\Publish\Core\Base\Container\Compiler;
use Symfony\Component\Config\Resource\FileResource;

if (!isset($installDir)) {
    throw new \RuntimeException('$installDir not provided to ' . __FILE__);
}

$containerBuilder = new ContainerBuilder();

// Track current file for changes
$containerBuilder->addResource(new FileResource(__FILE__));

// Cache settings (takes same env variables as ezplatform does, only supports "singleredis" setup)
if (getenv('CUSTOM_CACHE_POOL') === 'singleredis') {
    $igbinary = getenv('REDIS_ENABLE_IGBINARY');
    $lzf = getenv('REDIS_ENABLE_LZF');

    if ($igbinary && $lzf) {
        $class = \eZ\Bundle\EzPublishCoreBundle\Cache\Driver\Redis\RedisIgbinaryLzf::class;
    } elseif ($igbinary && !$lzf) {
        $class = \eZ\Bundle\EzPublishCoreBundle\Cache\Driver\Redis\RedisIgbinary::class;
    } elseif (!$igbinary && $lzf) {
        $class = \eZ\Bundle\EzPublishCoreBundle\Cache\Driver\Redis\RedisSerializeLzf::class;
    } else {
        $class = \Stash\Driver\Redis::class;
    }

    $containerBuilder
        ->register('ezpublish.cache_pool.driver', $class)
        ->addArgument(['servers' => [['server' => getenv('CACHE_HOST') ?: '127.0.0.1']]]);
}

$settingsPath = $installDir . '/eZ/Publish/Core/settings/';
$loader = new YamlFileLoader($containerBuilder, new FileLocator($settingsPath));

$loader->load('fieldtype_external_storages.yml');
$loader->load('fieldtype_services.yml');
$loader->load('fieldtypes.yml');
$loader->load('indexable_fieldtypes.yml');
$loader->load('io.yml');
$loader->load('repository.yml');
$loader->load('repository/inner.yml');
$loader->load('repository/signalslot.yml');
$loader->load('roles.yml');
$loader->load('storage_engines/common.yml');
$loader->load('storage_engines/cache.yml');
$loader->load('storage_engines/legacy.yml');
$loader->load('storage_engines/shortcuts.yml');
$loader->load('search_engines/common.yml');
$loader->load('settings.yml');
$loader->load('utils.yml');
$loader->load('tests/common.yml');
$loader->load('tests/integration_legacy_core.yml');

$containerBuilder->setParameter('ezpublish.kernel.root_dir', $installDir);

$containerBuilder->addCompilerPass(new Compiler\FieldTypeCollectionPass());
$containerBuilder->addCompilerPass(new Compiler\FieldTypeNameableCollectionPass());
$containerBuilder->addCompilerPass(new Compiler\RegisterLimitationTypePass());

$containerBuilder->addCompilerPass(new Compiler\Storage\ExternalStorageRegistryPass());
$containerBuilder->addCompilerPass(new Compiler\Storage\Legacy\FieldValueConverterRegistryPass());
$containerBuilder->addCompilerPass(new Compiler\Storage\Legacy\RoleLimitationConverterPass());

$containerBuilder->addCompilerPass(new Compiler\Search\Legacy\CriteriaConverterPass());
$containerBuilder->addCompilerPass(new Compiler\Search\Legacy\CriterionFieldValueHandlerRegistryPass());
$containerBuilder->addCompilerPass(new Compiler\Search\Legacy\SortClauseConverterPass());


return $containerBuilder;
