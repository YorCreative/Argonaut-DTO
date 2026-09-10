<?php

namespace YorCreative\ArgonautDTO;

use Closure;
use ReflectionClass;
use ReflectionProperty;
use YorCreative\ArgonautDTO\Traits\HasCasting;
use YorCreative\ArgonautDTO\Traits\HasFactories;
use YorCreative\ArgonautDTO\Traits\HasKeyMapping;
use YorCreative\ArgonautDTO\Traits\HasSerialization;
use YorCreative\ArgonautDTO\Traits\HasValidation;

abstract class ArgonautImmutableDTO implements ArgonautDTOContract
{
    use HasCasting;
    use HasFactories;
    use HasKeyMapping;
    use HasSerialization;
    use HasValidation;

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        $this->initializeFromAttributes($attributes);
    }

    /** @param array<string, mixed> $attributes */
    protected function initializeFromAttributes(array $attributes): void
    {
        $attributes = $this->mapInputKeys($attributes);

        foreach ($attributes as $key => $value) {
            if (property_exists($this, (string) $key) && ! $this->isInternalProperty((string) $key)) {
                $castValue = $value === null ? null : $this->castInputValue((string) $key, $value);
                $this->initializeReadonlyProperty((string) $key, $castValue);
            }
        }
    }

    protected function isInternalProperty(string $key): bool
    {
        return in_array($key, $this->getExcludedSerializationProperties(), true);
    }

    protected function initializeReadonlyProperty(string $key, mixed $value): void
    {
        self::assignInDeclaringScope($this, new ReflectionProperty($this, $key), $value);
    }

    /**
     * Assign a property on $target from the scope that declares it.
     *
     * PHP 8.3 only permits a readonly property to be initialized from its
     * declaring scope, and both ReflectionProperty::setValue() and a property
     * object obtained from a subclass carry the wrong one -- so initializing a
     * property a PARENT declares, on an instance of a subclass, failed there
     * while working on 8.4+, which relaxed the rule. Binding to the declaring
     * class satisfies 8.3 and changes nothing later. It also resolves to the
     * correct slot when a parent and a child both declare a private property
     * of the same name.
     */
    private static function assignInDeclaringScope(object $target, ReflectionProperty $property, mixed $value): void
    {
        $assign = Closure::bind(
            function (string $name, mixed $assigned): void {
                $this->{$name} = $assigned;
            },
            $target,
            $property->getDeclaringClass()->getName(),
        );

        $assign($property->getName(), $value);
    }

    /**
     * Return a copy with the given attributes applied.
     *
     * Unlike the mutable base class this cannot be a clone-and-reassign,
     * because readonly properties cannot be reassigned once set. Instead, an
     * uninitialized instance is built through reflection, every unchanged
     * property is copied across verbatim (already cast, not reprocessed), and
     * only the given $attributes are routed through the normal
     * initializeFromAttributes() -> castInputValue() path. This mirrors the
     * mutable class's semantics — only the changes are re-applied — which
     * matters because casting is not guaranteed idempotent: a custom cast is
     * a transformation, not a guard, and a nestedAssembler expects its source
     * shape, not an already-assembled DTO.
     *
     * This is a shallow copy: nested objects are shared with the original, not
     * duplicated. Mutating a nested DTO reached through the copy therefore
     * mutates the original too. Rebuild nested values explicitly if you need
     * them independent.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function with(array $attributes): static
    {
        // Only the CHANGED attributes go through casting. A full-state rebuild
        // would re-run the engine over already-cast values: built-in casts
        // survive on their identity guards, but a custom cast is a
        // transformation and would apply twice, and a nestedAssembler would
        // try to re-assemble an already-assembled DTO.
        // Internal keys are dropped from the changed set: initializeFromAttributes()
        // skips them, so treating one as "changed" would stop it being copied
        // from the original and silently reset it to its declared default.
        $changed = array_filter(
            array_keys($this->mapInputKeys($attributes)),
            fn (int|string $key): bool => ! $this->isInternalProperty((string) $key),
        );

        /** @var static $copy */
        $copy = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        $shadowed = [];

        foreach ($this->copyableProperties() as $property) {
            $name = $property->getName();

            // Properties arrive most-derived first, so the first slot seen for
            // a name is the one initializeFromAttributes() would write. Only
            // that one is skipped when the property is changing; a same-named
            // private slot declared further up the hierarchy is a separate
            // property and must still be copied, or it is left uninitialized.
            $isTarget = ! isset($shadowed[$name]);
            $shadowed[$name] = true;

            if (($isTarget && in_array($name, $changed, true)) || ! $property->isInitialized($this)) {
                continue;
            }

            self::assignInDeclaringScope($copy, $property, $property->getValue($this));
        }

        $copy->initializeFromAttributes($attributes);

        return $copy;
    }

    /**
     * Every instance property on this object, including private ones declared
     * by subclasses.
     *
     * get_object_vars($this) resolves in the scope it is called from, which is
     * this class -- a private property declared on a subclass is invisible to
     * it. Copying from that list left such a property at its declared default
     * on the copy, or, for a typed property with no default, uninitialized and
     * fatal on first read. Walking the hierarchy sees all of them.
     *
     * @return list<ReflectionProperty>
     */
    private function copyableProperties(): array
    {
        $properties = [];
        $seen = [];

        for ($class = new ReflectionClass($this); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                // Only a private property gets per-class identity. Two private
                // properties of the same name in a parent and a child really are
                // distinct slots, and de-duplicating on the name alone dropped
                // the parent's. A redeclared public or protected property is the
                // opposite case: parent and child share one slot, so treating
                // them as two writes it twice -- fatal when it is readonly.
                $id = $property->isPrivate()
                    ? $property->getDeclaringClass()->getName().'::'.$property->getName()
                    : $property->getName();

                if ($property->isStatic() || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $properties[] = $property;
            }
        }

        return $properties;
    }
}
