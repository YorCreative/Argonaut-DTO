<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use LogicException;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\Tests\Fixtures\AliasRulesDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\BrokenRulesDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\ClosureRulesDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\EmailDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\NoRulesDTO;
use YorCreative\ArgonautDTO\Tests\Fixtures\PipedRulesDTO;
use YorCreative\ArgonautDTO\ValidationException;

final class ValidationTest extends TestCase
{
    public function test_is_valid_propagates_exceptions_thrown_by_rules(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('rules() blew up');

        (new BrokenRulesDTO)->isValid();
    }

    public function test_is_valid_surfaces_a_missing_rules_method(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must implement a rules() method');

        (new NoRulesDTO)->isValid();
    }

    public function test_is_valid_throws_validation_exception_when_asked_to(): void
    {
        $this->expectException(ValidationException::class);

        (new EmailDTO(['email' => 'nope']))->isValid(throw: true);
    }

    public function test_is_valid_returns_false_for_invalid_data(): void
    {
        self::assertFalse((new EmailDTO(['email' => 'nope']))->isValid());
    }

    public function test_is_valid_returns_true_for_valid_data(): void
    {
        self::assertTrue((new EmailDTO(['email' => 'jane@example.com']))->isValid());
    }

    public function test_the_int_and_bool_aliases_accept_valid_input(): void
    {
        self::assertTrue((new AliasRulesDTO(['count' => 5, 'flag' => true]))->isValid());
        self::assertTrue((new AliasRulesDTO(['count' => '42', 'flag' => '0']))->isValid());
    }

    public function test_the_int_and_bool_aliases_reject_invalid_input(): void
    {
        $errors = (new AliasRulesDTO(['count' => 'abc', 'flag' => 'yes']))->validate(throw: false);

        self::assertIsArray($errors);
        self::assertArrayHasKey('count', $errors);
        self::assertArrayHasKey('flag', $errors);
    }

    public function test_aliases_are_normalized_in_the_piped_string_form(): void
    {
        self::assertTrue((new PipedRulesDTO(['count' => 7]))->isValid());
        self::assertFalse((new PipedRulesDTO(['count' => 'nope']))->isValid());
    }

    public function test_closure_rules_pass_through_normalization(): void
    {
        self::assertTrue((new ClosureRulesDTO(['name' => 'allowed']))->isValid());
    }

    public function test_closure_rules_report_their_own_failure_message(): void
    {
        $errors = (new ClosureRulesDTO(['name' => 'denied']))->validate(throw: false);

        self::assertIsArray($errors);
        self::assertSame(['The name must be allowed.'], $errors['name']);
    }

    public function test_validation_exception_exposes_errors(): void
    {
        $dto = new EmailDTO(['email' => 'nope']);

        try {
            $dto->validate();
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('email', $exception->errors());
            self::assertSame($exception->errors(), $exception->getErrors());
            self::assertSame('The DTO failed validation.', $exception->getMessage());
        }
    }
}
