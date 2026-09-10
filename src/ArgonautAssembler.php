<?php

namespace YorCreative\ArgonautDTO;

use BadFunctionCallException;
use BadMethodCallException;
use ReflectionMethod;

class ArgonautAssembler
{
    /** @var array<class-string, array<string, string>> */
    protected static array $methodMap = [];

    /** @var array<class-string, array<string, ReflectionMethod>> */
    protected static array $reflectionMap = [];

    /**
     * @param  iterable<int|string, object|array<string, mixed>>  $items
     * @return Collection<mixed>
     */
    public static function fromCollection(iterable $items, string $transformedInputClass, ?self $instance = null): Collection
    {
        $result = [];

        foreach ($items as $key => $item) {
            $result[$key] = static::assemble($item, $transformedInputClass, $instance);
        }

        return new Collection($result);
    }

    /**
     * @param  array<int|string, object|array<string, mixed>>  $items
     * @return Collection<mixed>
     */
    public static function fromArray(array $items, string $transformedInputClass, ?self $instance = null): Collection
    {
        return static::fromCollection($items, $transformedInputClass, $instance);
    }

    /** @param array<string, mixed>|object $input */
    public static function assemble(object|array $input, string $transformedInputClass, ?self $instance = null): mixed
    {
        $class = static::class;
        $method = static::$methodMap[$class][$transformedInputClass] ?? null;
        if ($method === null) {
            $method = static::resolveAssembleMethod($transformedInputClass);
            static::$methodMap[$class] ??= [];
            static::$methodMap[$class][$transformedInputClass] = $method;
        }
        $objectInput = is_array($input) ? (object) $input : $input;
        static::$reflectionMap[$class] ??= [];
        $reflectionMethod = static::$reflectionMap[$class][$method] ??= new ReflectionMethod($class, $method);

        if ($reflectionMethod->isStatic()) {
            return $class::$method($objectInput);
        }

        if ($instance !== null) {
            return $instance->{$method}($objectInput);
        }

        throw new BadMethodCallException("Cannot call instance method {$method} on [{$class}] without an instance.");
    }

    /** @param array<string, mixed> $input */
    public static function arrayAssemble(array $input, string $transformedInputClass, ?self $instance = null): mixed
    {
        return static::assemble($input, $transformedInputClass, $instance);
    }

    protected static function resolveAssembleMethod(string $assembledInputClass): string
    {
        $baseClassName = self::classBasename($assembledInputClass);
        $toMethod = 'to'.$baseClassName;

        if (method_exists(static::class, $toMethod)) {
            return $toMethod;
        }

        $fromMethod = 'from'.$baseClassName;
        if (method_exists(static::class, $fromMethod)) {
            return $fromMethod;
        }

        throw new BadFunctionCallException("Missing method [{$toMethod}] or [{$fromMethod}] for assembling to {$assembledInputClass} on [".static::class.']');
    }

    /** @param array<string, mixed>|object $input */
    public function assembleInstance(object|array $input, string $transformedInputClass): mixed
    {
        return static::assemble($input, $transformedInputClass, $this);
    }

    private static function classBasename(string $class): string
    {
        $class = trim($class, '\\');

        return str_contains($class, '\\') ? substr($class, strrpos($class, '\\') + 1) : $class;
    }
}
