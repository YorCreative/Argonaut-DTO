<?php

namespace YorCreative\ArgonautDTO;

use JsonSerializable;

interface ArgonautDTOContract extends JsonSerializable
{
    /** @return array<string, mixed> */
    public function toArray(?int $depth = null): array;

    public function toJson(int $options = 0, ?int $depth = null): string;

    /** @return true|array<string, list<string>> */
    public function validate(bool $throw = true): bool|array;
}
