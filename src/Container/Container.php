<?php declare(strict_types=1);

namespace Serendipity\Job\Container;

use Psr\Container\ContainerInterface;
use Serendipity\Job\Container\Exceptions\ContainerException;
use Serendipity\Job\Container\Exceptions\InstanceNotFoundException;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

class Container implements ContainerInterface
{
    public const USE_STORED_DEPENDENCIES = 0;
    public const USE_NEW_DEPENDENCIES = 1;

    protected array $instances = [];

    /** @var bool[][] */
    protected array $relationships = [];

    /** @var string[] */
    protected array $maps;

    public function __construct()
    {
        // Store this instance so we return it instead of a secondary instance when requested.
        $this->store($this);
    }

    /**
     * @throws ContainerException
     */
    public function get(string $id): object
    {
        $id = $this->getDefinitiveName($id);

        if (!$this->has($id)) {
            $instance = $this->build($id);
            $this->store($instance);
        }

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        $id = $this->getDefinitiveName($id);

        return isset($this->instances[$id]);
    }

    public function assertHas(string $name): void
    {
        if (!$this->has($name)) {
            throw InstanceNotFoundException::fromName($name);
        }
    }

    /**
     * Build a new instance with stored dependencies.
     *
     * @param string $name
     *
     * @return object
     * @throws \Serendipity\Job\Container\Exceptions\ContainerException
     */
    public function build(string $name): object
    {
        return $this->createInstance($name, self::USE_STORED_DEPENDENCIES);
    }

    /**
     * Build a new instance with new dependencies.
     *
     * @throws ContainerException
     */
    public function buildAll(string $name): object
    {
        return $this->createInstance($name, self::USE_NEW_DEPENDENCIES);
    }

    /**
     * Map the requested name to the replacement name. When the requested
     * name is retrieved, the replacement name will be used to build the instance.
     */
    public function map(string $requested, string $replacement): void
    {
        $this->maps[$this->normalize($requested)] = $this->normalize($replacement);
    }

    public function unmap(string $requested): void
    {
        unset($this->maps[$requested]);
        $this->clear($requested);
    }

    public function getDependenciesFor(
        string $name,
        int $useStoredDependencies = self::USE_STORED_DEPENDENCIES
    ): array {
        $name = $this->getDefinitiveName($name);

        try {
            /*
             * We @ quiet this so we can be more specific in our exceptions later
             * (i.e. due to non-optional parameters)
             */
            $reflection = @new ReflectionClass($name);
        } catch (Throwable $t) {
            throw new ContainerException(
                sprintf(
                    'Could not create instance for class `%s`.',
                    $name
                ),
                (int)$t->getCode(),
                $t
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $parameters = $constructor->getParameters();

        $dependencies = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            $builtIn = false;

            if ($type instanceof ReflectionNamedType) {
                $builtIn = $type->isBuiltin();
            }

            $class = null;

            if ($builtIn === false && $parameter->getType() instanceof ReflectionNamedType  ) {
                /** @var ReflectionNamedType $type */
                $type = $parameter->getType();
                $class = new ReflectionClass($type->getName());
            }

            if ($class === null) {
                if (!$parameter->isOptional()) {
                    throw new ContainerException(sprintf(
                        'Cannot inject value for non-optional constructor parameter `$%s` without a default value.',
                        $parameter->name
                    ));
                }

                $dependencies[] = $parameter->getDefaultValue();

                // For the foreach loop
                continue;
            }

            $dependencyName = $this->getDefinitiveName($class->name);

            $this->storeRelationship($name, $dependencyName);

            if ($useStoredDependencies === self::USE_NEW_DEPENDENCIES) {
                $dependencies[] = $this->build($dependencyName);
            } elseif ($useStoredDependencies === self::USE_STORED_DEPENDENCIES) {
                $dependencies[] = $this->get($dependencyName);
            } else {
                throw new ContainerException(sprintf(
                    'Invalid dependency type value passed: `%d`.',
                    $useStoredDependencies
                ));
            }
        }

        return $dependencies;
    }

    public function store(object $instance, string $name = null): void
    {
        $name = $this->getDefinitiveName($name ?? $instance::class);

        $this->instances[$name] = $instance;
    }

    public function clear(string $name): void
    {
        $name = $this->getDefinitiveName($name);

        if (!$this->has($name)) {
            return;
        }

        unset($this->instances[$name]);

        $this->clearRelationship($name);
    }

    /**
     * @param string[] $keep
     *
     */
    public function clearExcept(array $keep): void
    {
        foreach ($keep as $name) {
            $this->assertHas($name);
        }

        foreach ($this->instances as $name => $instance) {
            if (!in_array($name, $keep, true)) {
                $this->clear($name);
            }
        }
    }

    /**
     * Clear all instances and all relationships.
     */
    public function clearAll(): void
    {
        $this->instances = [];
        $this->relationships = [];
    }

    /**
     * Create an instance with either new or existing dependencies.
     *
     * @throws ContainerException
     */
    protected function createInstance(string $name, int $useStoredDependencies): object
    {
        $name = $this->getDefinitiveName($name);

        if (interface_exists($name)) {
            throw new ContainerException(sprintf(
                'Cannot create instance for interface `%s`.',
                $name
            ));
        }

        try {
            $dependencies = $this->getDependenciesFor($name, $useStoredDependencies);
        } catch (Throwable $t) {
            throw new ContainerException($t->getMessage(), (int)$t->getCode(), $t);
        }

        return new $name(...$dependencies);
    }

    /**
     * Return the mapping if it exists, otherwise just return the requested name.
     */
    protected function getMapIfExists(string $requested): string
    {
        return $this->maps[$requested] ?? $requested;
    }

    /**
     * Store the relationship between the two items.
     *
     * @throws ContainerException
     */
    protected function storeRelationship(string $class, string $dependency): void
    {
        $this->relationships[$class][$dependency] = true;

        if (isset(
            $this->relationships[$class][$dependency],
            $this->relationships[$dependency][$class]
        )) {
            throw new ContainerException(sprintf(
                'Cyclical dependency found between `%s` and `%s`.',
                $class,
                $dependency
            ));
        }
    }

    /**
     * Clear the relationship for the provided id.
     */
    protected function clearRelationship(string $name): void
    {
        // Clear from the left
        unset($this->relationships[$name]);

        // And clear from the right
        foreach ($this->relationships as $left => &$objectNames) {
            if (isset($objectNames[$name])) {
                unset($objectNames[$name]);
            }
        }
    }

    /**
     * Normalize the name so it never has a prefixed \, and return
     * the most appropriate name based on what's being requested.
     */
    protected function normalize(string $name): string
    {
        return ltrim(trim($name), '\\');
    }

    /**
     * Get the definitive name for the provided string. If it's mapped,
     * get the replacement name. Always makes sure the name is normalized.
     */
    protected function getDefinitiveName(string $name): string
    {
        return $this->getMapIfExists($this->normalize($name));
    }
}
