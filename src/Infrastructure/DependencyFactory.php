<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Smoothie\ResumeExporter\Application\Resume\ResumeFactory as WriteResumeFactory;
use Smoothie\ResumeExporter\Application\Resume\ResumeRepository as WriteResumeRepository;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemRepository;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemsFactory;
use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;
use Smoothie\ResumeExporter\Domain\Resume\ResumeFactory as ReadResumeFactory;
use Smoothie\ResumeExporter\Domain\Resume\ResumeRepository as ReadResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\File\DomPdfBuilder;
use Smoothie\ResumeExporter\Infrastructure\File\DomPdfRepository;
use Smoothie\ResumeExporter\Infrastructure\File\FilesystemRepository;
use Smoothie\ResumeExporter\Infrastructure\File\JsonResumeFactory;
use Smoothie\ResumeExporter\Infrastructure\File\JsonResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\File\PdfResumeFactory;
use Smoothie\ResumeExporter\Infrastructure\File\PdfResumeRepository;
use Smoothie\ResumeExporter\Infrastructure\File\TwigFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemRepository;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessMapItemsFactory;
use Smoothie\ResumeExporter\Infrastructure\Mapping\PropertyAccessStrategy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DependencyFactory
{
    /** @var array<string, bool> */
    private array $inResolution = [];

    /** @var object[]|callable[] */
    private array $dependencies = [];

    /** @var callable[] */
    private array $factories = [];

    private bool $frozen = false;

    public function __construct(private Filesystem $filesystem, private ValidatorInterface $validator)
    {
    }

    public function getDomPdfRepository(): DomPdfRepository
    {
        return $this->getDependency(
            DomPdfRepository::class,
            function (): DomPdfRepository {
                $filesystem = $this->getFilesystemRepository();
                $twigFactory = $this->getTwigFactory();
                $domPdfBuilder = $this->getDomPdfBuilder();

                return new DomPdfRepository(
                    $filesystem,
                    $twigFactory,
                    $domPdfBuilder,
                );
            },
        );
    }

    public function getFilesystemRepository(): FilesystemRepository
    {
        return $this->getDependency(
            FilesystemRepository::class,
            fn (): FilesystemRepository => new FilesystemRepository($this->filesystem),
        );
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->getDependency(
            ValidatorInterface::class,
            fn (): ValidatorInterface => $this->validator,
        );
    }

    public function getTwigFactory(): TwigFactory
    {
        return $this->getDependency(
            TwigFactory::class,
            static fn (): TwigFactory => new TwigFactory(),
        );
    }

    public function getDomPdfBuilder(): DomPdfBuilder
    {
        return $this->getDependency(
            DomPdfBuilder::class,
            static fn (): DomPdfBuilder => new DomPdfBuilder(),
        );
    }

    public function getPdfResumeFactory(): WriteResumeFactory
    {
        return $this->getDependency(
            WriteResumeFactory::class,
            static fn (): WriteResumeFactory => new PdfResumeFactory(),
        );
    }

    public function getReadResumeFactory(): ReadResumeFactory
    {
        return $this->getDependency(
            ReadResumeFactory::class,
            fn (): ReadResumeFactory => new JsonResumeFactory($this->getValidator()),
        );
    }

    public function getWriteResumeRepository(): WriteResumeRepository
    {
        return $this->getDependency(
            WriteResumeRepository::class,
            function (): PdfResumeRepository {
                $mappingStrategy = $this->getMappingStrategy();
                $resumeFactory = $this->getPdfResumeFactory();
                $domPdfRepository = $this->getDomPdfRepository();

                return new PdfResumeRepository(
                    $mappingStrategy,
                    $resumeFactory,
                    $domPdfRepository,
                );
            },
        );
    }

    public function getReadResumeRepository(): ReadResumeRepository
    {
        return $this->getDependency(
            ReadResumeRepository::class,
            function (): JsonResumeRepository {
                $mappingStrategy = $this->getMappingStrategy();
                $resumeFactory = $this->getReadResumeFactory();
                $propertyAccessor = $this->getPropertyAccessor();

                return new JsonResumeRepository(
                    $mappingStrategy,
                    $resumeFactory,
                    $propertyAccessor,
                );
            },
        );
    }

    public function getMappingStrategy(): MappingStrategy
    {
        return $this->getDependency(
            MappingStrategy::class,
            function (): MappingStrategy {
                $propertyAccessor = $this->getPropertyAccessor();
                $logger = $this->getLogger();
                $propertyAccessMapItemsFactory = $this->getPropertyAccessMapItemsFactory();
                $mapItemRepository = $this->getMapItemRepository();

                return new PropertyAccessStrategy(
                    propertyAccessor: $propertyAccessor,
                    logger: $logger,
                    propertyAccessMapItemsFactory: $propertyAccessMapItemsFactory,
                    mapItemRepository: $mapItemRepository,
                );
            },
        );
    }

    public function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->getDependency(
            PropertyAccessorInterface::class,
            static fn (): PropertyAccessorInterface => new PropertyAccessor(),
        );
    }

    public function getLogger(): LoggerInterface
    {
        return $this->getDependency(
            LoggerInterface::class,
            static fn (): LoggerInterface => new NullLogger(),
        );
    }

    public function getPropertyAccessMapItemsFactory(): MapItemsFactory
    {
        return $this->getDependency(
            MapItemsFactory::class,
            static function (): MapItemsFactory {
                return new PropertyAccessMapItemsFactory();
            },
        );
    }

    public function getMapItemRepository(): MapItemRepository
    {
        return $this->getDependency(
            MapItemRepository::class,
            function (): MapItemRepository {
                $propertyAccessor = $this->getPropertyAccessor();

                return new PropertyAccessMapItemRepository($propertyAccessor);
            },
        );
    }

    public function setDefinition(string $id, callable $service): void
    {
        $this->assertNotFrozen();
        $this->factories[$id] = $service;
    }

    public function setService(string $id, object|callable $service): void
    {
        $this->assertNotFrozen();
        $this->dependencies[$id] = $service;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    private function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw new \RuntimeException('Frozen');
        }
    }

    private function getDependency(string $id, callable $callback): mixed
    {
        if (! isset($this->inResolution[$id]) && \array_key_exists($id, $this->factories) && ! \array_key_exists(
            $id,
            $this->dependencies,
        )) {
            $this->inResolution[$id] = true;
            $this->dependencies[$id] = \call_user_func($this->factories[$id], $this);
            $this->inResolution = [];
        }

        if (! \array_key_exists($id, $this->dependencies)) {
            $this->dependencies[$id] = $callback();
        }

        return $this->dependencies[$id];
    }
}
