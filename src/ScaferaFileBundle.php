<?php

declare(strict_types=1);

namespace Scafera\File;

use Scafera\Kernel\InstalledPackages;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ScaferaFileBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->set(UploadExtractor::class)
                ->public()
            ->set(UploadValidator::class)
                ->public()

            // Event listener
            ->set(Listener\FileResponseListener::class)
                ->tag('kernel.event_subscriber')

            // Validator
            ->set(Validator\FileBoundaryValidator::class)
                ->tag('scafera.validator');

        // FileStorage depends on architecture declaring a storage directory
        $projectDir = $builder->getParameter('kernel.project_dir');
        $architecture = InstalledPackages::resolveArchitecture($projectDir);
        $storageDir = $architecture?->getStorageDir();

        if ($storageDir !== null) {
            $container->services()
                ->set(FileStorage::class)
                    ->args([$projectDir . '/' . $storageDir])
                    ->public();
        }
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DependencyInjection\FileBoundaryPass());
    }
}
