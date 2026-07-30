<?php

namespace YorCreative\ArgonautDTO\Traits;

use LogicException;
use YorCreative\ArgonautDTO\ValidationException;
use YorCreative\DataValidation\Validator;

trait HasValidation
{
    /** @return true|array<string, list<string>> */
    public function validate(bool $throw = true): bool|array
    {
        if (! method_exists($this, 'rules')) {
            throw new LogicException(static::class.' must implement a rules() method for validation.');
        }

        /** @var array<string, mixed> $data */
        $data = $this->toArray();
        $rules = $this->normalizeValidationRules($this->rules(), $data);
        $errors = [];

        // Keep sibling attributes isolated while preserving the complete DTO
        // data for cross-field rules such as `same`.
        foreach ($rules as $attribute => $ruleSet) {
            $validator = Validator::make($data, [$attribute => $ruleSet]);

            if (! $validator->validate()) {
                $errors = array_merge($errors, $validator->errors());
            }
        }

        if ($errors === []) {
            return true;
        }

        if ($throw) {
            throw new ValidationException($errors);
        }

        return $errors;
    }

    /**
     * Report whether the DTO satisfies its own rules().
     *
     * Only validation failure is reported as false. A missing or broken
     * rules() method is a programming error and is allowed to surface.
     *
     * @throws ValidationException when $throw is true and validation fails.
     */
    public function isValid(bool $throw = false): bool
    {
        return $this->validate($throw) === true;
    }

    /**
     * Preserve Argonaut aliases while passing rules to DataValidation.
     *
     * @param  array<string, string|array<int, mixed>>  $rules
     * @param  array<string, mixed>  $data
     * @return array<string, string|array<int, mixed>>
     */
    private function normalizeValidationRules(array $rules, array $data): array
    {
        $normalized = [];

        foreach ($rules as $attribute => $ruleSet) {
            $ruleList = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

            if (in_array('sometimes', $ruleList, true) && ! $this->hasValidationValue($data, $attribute)) {
                continue;
            }

            $ruleList = array_map(function (mixed $rule): mixed {
                if (! is_string($rule)) {
                    return $rule;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $name = match ($name) {
                    'int' => 'integer',
                    'bool' => 'boolean',
                    'collection' => 'array',
                    'sometimes' => null,
                    default => $name,
                };

                return $name === null ? null : $name.($parameter !== null ? ':'.$parameter : '');
            }, $ruleList);

            $ruleList = array_values(array_filter($ruleList, static fn (mixed $rule): bool => $rule !== null));
            $normalized[$attribute] = is_string($ruleSet) ? implode('|', $ruleList) : $ruleList;
        }

        return $normalized;
    }

    /** @param array<string, mixed> $data */
    private function hasValidationValue(array $data, string $attribute): bool
    {
        $value = $data;

        foreach (explode('.', $attribute) as $segment) {
            if ($segment === '*' || ! is_array($value) || ! array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
    }
}
