<?php

namespace YorCreative\ArgonautDTO;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @param array<string, list<string>> $validationErrors */
    public function __construct(private readonly array $validationErrors)
    {
        parent::__construct('The DTO failed validation.');
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->validationErrors;
    }

    /** @return array<string, list<string>> */
    public function getErrors(): array
    {
        return $this->errors();
    }
}
