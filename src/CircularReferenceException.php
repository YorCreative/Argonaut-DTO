<?php

namespace YorCreative\ArgonautDTO;

use RuntimeException;

/**
 * Raised when serialization re-enters a DTO instance that is already being
 * serialized further up the call stack.
 */
class CircularReferenceException extends RuntimeException
{
    public function __construct(private readonly string $dtoClass)
    {
        parent::__construct(
            "Cannot serialize {$dtoClass}: the DTO graph contains a circular reference back to this instance."
        );
    }

    public function dtoClass(): string
    {
        return $this->dtoClass;
    }
}
