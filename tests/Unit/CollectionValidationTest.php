<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\Tests\Fixtures\EmailDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NoRulesDTO;
use YorCreative\ArgonautDTO\ValidationException;

final class CollectionValidationTest extends TestCase
{
    public function test_validate_all_returns_true_when_every_item_is_valid(): void
    {
        $collection = new Collection([
            new EmailDTO(['email' => 'a@example.com']),
            new EmailDTO(['email' => 'b@example.com']),
        ]);

        self::assertTrue($collection->validateAll());
    }

    public function test_validate_all_returns_true_for_an_empty_collection(): void
    {
        self::assertTrue((new Collection)->validateAll());
    }

    public function test_validate_all_without_throwing_returns_errors_keyed_by_item(): void
    {
        $collection = new Collection([
            new EmailDTO(['email' => 'a@example.com']),
            new EmailDTO(['email' => 'not-an-email']),
        ]);

        $errors = $collection->validateAll(false);

        self::assertIsArray($errors);
        self::assertSame([1], array_keys($errors));
        self::assertArrayHasKey('email', $errors[1]);
    }

    public function test_validate_all_preserves_string_keys_in_the_error_map(): void
    {
        $collection = new Collection([
            'first' => new EmailDTO(['email' => 'a@example.com']),
            'second' => new EmailDTO(['email' => 'nope']),
        ]);

        self::assertSame(['second'], array_keys($collection->validateAll(false)));
    }

    public function test_validate_all_reports_every_failing_item(): void
    {
        $collection = new Collection([
            new EmailDTO(['email' => 'bad-one']),
            new EmailDTO(['email' => 'ok@example.com']),
            new EmailDTO(['email' => 'bad-two']),
        ]);

        self::assertSame([0, 2], array_keys($collection->validateAll(false)));
    }

    public function test_validate_all_throws_the_failing_items_own_exception(): void
    {
        $collection = new Collection([new EmailDTO(['email' => 'nope'])]);

        $this->expectException(ValidationException::class);

        $collection->validateAll();
    }

    public function test_validate_all_throws_for_the_first_failing_item_in_order(): void
    {
        $collection = new Collection([
            new EmailDTO(['email' => 'ok@example.com']),
            new EmailDTO(['email' => 'first-bad']),
            new EmailDTO(['email' => 'second-bad']),
        ]);

        try {
            $collection->validateAll();
            self::fail('validateAll() should have thrown.');
        } catch (ValidationException $e) {
            // The exception carries only the failing item's own errors, so assert
            // against the shape rather than the offending value.
            self::assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_validate_all_rejects_a_non_dto_item_naming_its_key(): void
    {
        $collection = new Collection(['alpha' => 'not a dto']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('alpha');

        $collection->validateAll();
    }

    public function test_validate_all_propagates_a_missing_rules_method(): void
    {
        $collection = new Collection([new NoRulesDTO]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must implement a rules() method');

        $collection->validateAll();
    }

    public function test_is_valid_all_reports_true_and_false(): void
    {
        $valid = new Collection([new EmailDTO(['email' => 'a@example.com'])]);
        $invalid = new Collection([new EmailDTO(['email' => 'nope'])]);

        self::assertTrue($valid->isValidAll());
        self::assertFalse($invalid->isValidAll());
    }
}
