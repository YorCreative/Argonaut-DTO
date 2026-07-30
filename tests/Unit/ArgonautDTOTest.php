<?php

namespace YorCreative\ArgonautDTO\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use YorCreative\ArgonautDTO\ArgonautAssembler;
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\ArgonautImmutableDTO;
use YorCreative\ArgonautDTO\Collection;
use YorCreative\ArgonautDTO\ValidationException;

enum TestStatus: string
{
    case Active = 'active';
}

final class TestItemDTO extends ArgonautDTO
{
    public string $name;
}

final class TestUserDTO extends ArgonautDTO
{
    public string $name;

    public string $email;

    public ?DateTimeImmutable $createdAt = null;

    public ?TestStatus $status = null;

    protected array $casts = [
        'name' => 'string',
        'email' => 'string',
        'createdAt' => DateTimeImmutable::class,
        'status' => TestStatus::class,
    ];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}

final class TestOrderDTO extends ArgonautDTO
{
    public array $items;

    public Collection $events;

    public ?TestUserDTO $user = null;

    protected array $casts = [
        'items' => [TestItemDTO::class],
        'events' => Collection::class.':'.TestItemDTO::class,
        'user' => TestUserDTO::class,
    ];
}

final class TestImmutableDTO extends ArgonautImmutableDTO
{
    public readonly string $name;

    public readonly ?TestStatus $status;

    protected array $casts = ['status' => TestStatus::class];
}

final class TestCollectionValidationDTO extends ArgonautDTO
{
    public array $items;

    public function rules(): array
    {
        return [
            'items' => ['sometimes', 'required', 'collection', 'min:1'],
        ];
    }
}

final class TestUserAssembler extends ArgonautAssembler
{
    public static function toTestUserDTO(object $input): TestUserDTO
    {
        return new TestUserDTO([
            'name' => $input->display_name,
            'email' => $input->email,
        ]);
    }
}

final class ArgonautDTOTest extends TestCase
{
    public function test_casts_nested_values_and_serializes_them(): void
    {
        $order = new TestOrderDTO([
            'items' => [['name' => 'Desk'], ['name' => 'Chair']],
            'events' => [['name' => 'created']],
            'user' => ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);

        self::assertInstanceOf(TestItemDTO::class, $order->items[0]);
        self::assertInstanceOf(Collection::class, $order->events);
        self::assertInstanceOf(TestUserDTO::class, $order->user);
        self::assertSame('created', $order->toArray()['events'][0]['name']);
        self::assertSame('jane@example.com', $order->toArray()['user']['email']);
    }

    public function test_casts_dates_enums_and_scalars(): void
    {
        $user = new TestUserDTO([
            'name' => 123,
            'email' => 'jane@example.com',
            'createdAt' => '2026-01-01',
            'status' => 'active',
        ]);

        self::assertSame('123', $user->name);
        self::assertInstanceOf(DateTimeImmutable::class, $user->createdAt);
        self::assertSame(TestStatus::Active, $user->status);
        self::assertSame('active', $user->toArray()['status']);
    }

    public function test_mutable_dto_supports_setters_filters_and_merge(): void
    {
        $user = new class(['firstName' => 'Jane']) extends ArgonautDTO
        {
            public string $firstName;

            public string $displayName = '';

            public function setFirstName(string $value): void
            {
                $this->firstName = $value;
                $this->displayName = strtoupper($value);
            }
        };

        $user->merge(['firstName' => 'Alex']);

        self::assertSame('ALEX', $user->displayName);
        self::assertSame(['firstName' => 'Alex'], $user->only('firstName'));
        self::assertArrayNotHasKey('firstName', $user->except('firstName'));
    }

    public function test_immutable_dto_initializes_readonly_properties(): void
    {
        $dto = new TestImmutableDTO(['name' => 'Jane', 'status' => 'active']);

        self::assertSame('Jane', $dto->name);
        self::assertSame(TestStatus::Active, $dto->status);
        self::assertSame(['name' => 'Jane', 'status' => 'active'], $dto->toArray());
    }

    public function test_validation_can_return_errors_or_throw(): void
    {
        $user = new TestUserDTO(['name' => 'Jane', 'email' => 'invalid']);

        self::assertFalse($user->isValid());
        self::assertArrayHasKey('email', $user->validate(throw: false));

        $this->expectException(ValidationException::class);
        $user->validate();
    }

    public function test_validation_uses_data_validation_aliases(): void
    {
        $missing = new TestCollectionValidationDTO;
        $valid = new TestCollectionValidationDTO(['items' => [['value' => 'one']]]);
        $invalid = new TestCollectionValidationDTO(['items' => []]);

        self::assertTrue($missing->isValid());
        self::assertTrue($valid->isValid());
        self::assertFalse($invalid->isValid());
    }

    public function test_assembler_supports_single_and_batch_transforms(): void
    {
        $payload = ['display_name' => 'Jane', 'email' => 'jane@example.com'];
        $user = TestUserAssembler::assemble($payload, TestUserDTO::class);
        $users = TestUserAssembler::fromArray([$payload, $payload], TestUserDTO::class);

        self::assertInstanceOf(TestUserDTO::class, $user);
        self::assertCount(2, $users);
        self::assertSame('Jane', $users->first()->name);
    }
}
