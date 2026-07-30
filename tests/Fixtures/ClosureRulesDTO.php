<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * Uses a DataValidation closure rule, which must survive alias normalization
 * untouched.
 */
class ClosureRulesDTO extends ArgonautDTO
{
    public mixed $name = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                function (string $field, mixed $value, callable $fail, array $data): bool {
                    if ($value !== 'allowed') {
                        $fail("The {$field} must be allowed.");

                        return false;
                    }

                    return true;
                },
            ],
        ];
    }
}
