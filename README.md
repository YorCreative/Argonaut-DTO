<br />
<br />

<div align="center">
  <a href="https://github.com/YorCreative">
    <img src="content/argonaut-dto.png" alt="Logo" width="240" height="203">
  </a>
</div>

<h3 align="center">Argonaut DTO</h3>

<div align="center">
<a href="https://github.com/YorCreative/Argonaut-DTO/blob/main/LICENSE"><img alt="GitHub license" src="https://img.shields.io/github/license/YorCreative/Argonaut-DTO"></a>
<a href="https://github.com/YorCreative/Argonaut-DTO/stargazers"><img alt="GitHub stars" src="https://img.shields.io/github/stars/YorCreative/Argonaut-DTO?label=Repo%20Stars"></a>
<img alt="GitHub Org's stars" src="https://img.shields.io/github/stars/YorCreative?style=social&label=YorCreative%20Stars&link=https%3A%2F%2Fgithub.com%2FYorCreative">
<a href="https://github.com/YorCreative/Argonaut-DTO/issues"><img alt="GitHub issues" src="https://img.shields.io/github/issues/YorCreative/Argonaut-DTO"></a>
<a href="https://github.com/YorCreative/Argonaut-DTO/network"><img alt="GitHub forks" src="https://img.shields.io/github/forks/YorCreative/Argonaut-DTO"></a>
<img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/yorcreative/argonaut-dto?color=green">
<a href="https://github.com/YorCreative/Argonaut-DTO/actions/workflows/tests.yml"><img alt="Tests" src="https://github.com/YorCreative/Argonaut-DTO/actions/workflows/tests.yml/badge.svg"></a>
<a href="https://github.com/YorCreative/Argonaut-DTO/actions/workflows/security.yml"><img alt="Security" src="https://github.com/YorCreative/Argonaut-DTO/actions/workflows/security.yml/badge.svg"></a>
</div>

Framework-agnostic Data Transfer Objects for PHP 8.3+. Argonaut DTO provides the useful parts of Laravel Argonaut DTO
without requiring Laravel: nested DTO and enum casting, recursive serialization, mutable and immutable DTOs,
convention-based assemblers, and lightweight validation.

## Requirements

- PHP 8.3, 8.4, or 8.5

## Installation

Install via Composer:

```bash
composer require yorcreative/argonaut-dto
```

## Basic DTO

```php
use YorCreative\ArgonautDTO\ArgonautDTO;

final class UserDTO extends ArgonautDTO
{
    public string $name;
    public string $email;

    protected array $casts = [
        'name' => 'string',
        'email' => 'string',
    ];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}

$user = new UserDTO(['name' => 'Jane', 'email' => 'jane@example.com']);
$user->merge(['name' => 'Jane Doe']);

$user->toArray();
$user->toJson();
$user->isValid();
```

Unknown input keys are ignored. A setter named `set<FieldName>` takes precedence over direct property assignment. Declare `$prioritizedAttributes` when setters must run before the remaining input attributes.

## Named constructors

```php
$profile = ProfileDTO::fromArray(['fullName' => 'Ada Lovelace']);
$profile = ProfileDTO::fromJson('{"fullName":"Ada Lovelace"}');
$tags    = TagDTO::collection([['name' => 'a'], ['name' => 'b']]); // Collection<TagDTO>
```

`fromArray()` and `fromJson()` are new in 1.1.0. `collection()` is not new — it
has existed since 1.0.0 and is listed here because 1.1.0 makes its return type
generic, so static analysis now narrows the elements.

`fromJson()` throws `JsonException` on malformed JSON. It also throws
`JsonException` when the JSON is valid but does not decode to an object — for
example `'null'`, `'"a string"'`, `'123'`, or a JSON array such as
`'[{"fullName":"Ada"}]'` or `'[1,2]'` — with a message naming the type it
found instead, such as `ProfileDTO::fromJson() expects a JSON object, null
given.` An empty array (`'[]'`) is indistinguishable from an empty object
(`'{}'`) once decoded, so both are accepted and produce an empty DTO. All
three named constructors are available on both `ArgonautDTO` and
`ArgonautImmutableDTO`.

## Nested casts

```php
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Collection;

final class OrderDTO extends ArgonautDTO
{
    public array $items;
    public Collection $history;
    public ?UserDTO $customer = null;

    protected array $casts = [
        'items' => [OrderItemDTO::class],
        'history' => Collection::class.':'.OrderEventDTO::class,
        'customer' => UserDTO::class,
    ];
}
```

Array casts use `[SomeDTO::class]`. Collection casts use `Collection::class . ':' . SomeDTO::class` (the shorthand `collection:SomeDTO` is also supported). Arrays, traversables, and the package `Collection` can be used as input.

Backed enums and `DateTimeInterface` implementations can be cast directly:

```php
protected array $casts = [
    'status' => OrderStatus::class,
    'createdAt' => DateTimeImmutable::class,
];
```

Nested DTOs and backed enums serialize recursively; enums serialize to their backing values.

## Attribute casting

Casts can be declared with attributes instead of the `$casts` array:

```php
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastCollection;
use YorCreative\ArgonautDTO\Attributes\CastEnum;
use YorCreative\ArgonautDTO\Attributes\CastTo;
use YorCreative\ArgonautDTO\Collection;

class OrderDTO extends ArgonautDTO
{
    #[CastTo(CustomerDTO::class)]
    public ?CustomerDTO $customer = null;

    /** @var array<int, LineDTO> */
    #[CastTo(LineDTO::class, many: true)]
    public array $lines = [];

    /** @var Collection<LineDTO> */
    #[CastCollection(LineDTO::class)]
    public ?Collection $lineCollection = null;

    #[CastEnum(Status::class)]
    public ?Status $status = null;
}
```

Each attribute is equivalent to the `$casts` entry it replaces:

| Attribute | Equivalent `$casts` value |
| --- | --- |
| `#[CastTo(LineDTO::class)]` | `LineDTO::class` |
| `#[CastTo(LineDTO::class, many: true)]` | `[LineDTO::class]` |
| `#[CastCollection(LineDTO::class)]` | `'collection:'.LineDTO::class` |
| `#[CastEnum(Status::class)]` | `Status::class` |

Attributes and the `$casts` array can coexist. When both describe the same
property, **the `$casts` array wins**. Attributes are fixed at the point of
property declaration, so a subclass cannot change the attribute on a property it
inherits without redeclaring that property — `$casts` is its lever short of
redeclaration, and this rule keeps that override working:

```php
class ParentDTO extends ArgonautDTO
{
    #[CastTo(TagDTO::class)]
    public mixed $thing = null;
}

class ChildDTO extends ParentDTO
{
    // Overrides the inherited attribute.
    protected array $casts = ['thing' => 'string'];
}
```

Like `$casts`, cast attributes only apply on the direct property-assignment
path. If the DTO declares a `set<Property>()` setter for that property, the
setter runs instead and is responsible for its own conversion — the attribute
is silently never consulted.

### Custom cast classes

For a conversion the library doesn't know how to do — a value object, a
domain-specific formatter — implement `CastsArgonautAttribute`:

```php
interface CastsArgonautAttribute
{
    public function get(string $key, mixed $value): mixed;
}
```

Declare it the same way as any other cast — as a `$casts` entry or as a
`#[CastWith]` attribute:

```php
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastWith;
use YorCreative\ArgonautDTO\CastsArgonautAttribute;

class MoneyCast implements CastsArgonautAttribute
{
    public function get(string $key, mixed $value): mixed
    {
        return sprintf('$%0.2f', $value / 100);
    }
}

class OrderDTO extends ArgonautDTO
{
    protected array $casts = ['price' => MoneyCast::class];

    public mixed $price = null;
}

class InvoiceDTO extends ArgonautDTO
{
    #[CastWith(MoneyCast::class)]
    public mixed $total = null;
}

(new OrderDTO(['price' => 1999]))->price;    // '$19.99'
(new InvoiceDTO(['total' => 4250]))->total;  // '$42.50'
```

A custom cast works with all three cast container forms used elsewhere in
this library, with either declaration:

| `$casts` value | `#[CastWith]` equivalent | Behavior |
| --- | --- | --- |
| `MoneyCast::class` | `#[CastWith(MoneyCast::class)]` | Applied to the value once |
| `[MoneyCast::class]` | `#[CastWith(MoneyCast::class, many: true)]` | Value is iterated as an array; applied to each item |
| `'collection:'.MoneyCast::class` | `#[CastWith('collection:'.MoneyCast::class)]` | Value is iterated as a `Collection`; applied to each item, result is a `Collection` |

```php
class OrderDTO extends ArgonautDTO
{
    protected array $casts = [
        'price' => MoneyCast::class,
        'lineTotals' => [MoneyCast::class],
        'refunds' => 'collection:'.MoneyCast::class,
    ];

    public mixed $price = null;

    /** @var array<int, mixed> */
    public array $lineTotals = [];

    /** @var Collection<mixed> */
    public ?Collection $refunds = null;
}

$order = new OrderDTO([
    'price' => 1999,
    'lineTotals' => [500, 750],
    'refunds' => [100],
]);

$order->price;              // '$19.99'
$order->lineTotals;         // ['$5.00', '$7.50']
$order->refunds->all();     // ['$1.00']
```

The same three forms via `#[CastWith]`:

```php
class InvoiceDTO extends ArgonautDTO
{
    #[CastWith(MoneyCast::class)]
    public mixed $total = null;

    /** @var array<int, mixed> */
    #[CastWith(MoneyCast::class, many: true)]
    public array $lineTotals = [];

    /** @var Collection<mixed> */
    #[CastWith('collection:'.MoneyCast::class)]
    public ?Collection $refunds = null;
}

$invoice = new InvoiceDTO([
    'total' => 4250,
    'lineTotals' => [500, 750],
    'refunds' => [100],
]);

$invoice->total;              // '$42.50'
$invoice->lineTotals;         // ['$5.00', '$7.50']
$invoice->refunds->all();     // ['$1.00']
```

Where a property has both a `$casts` entry and a `#[CastWith]` attribute,
**`$casts` wins** — the same precedence rule as the other cast attributes:

```php
class ConflictingDTO extends ArgonautDTO
{
    protected array $casts = ['name' => 'string']; // wins over the attribute below

    #[CastWith(UppercaseCast::class)]
    public mixed $name = null;
}

(new ConflictingDTO(['name' => 'ada']))->name;  // 'ada', not 'ADA'
```

A custom cast never receives `null` — `setAttribute()` short-circuits null
before casting reaches it, so implementations don't need a null check.

Implementations **must be stateless and constructible with no arguments**.
One instance is created per cast class and reused across every DTO that uses
it — a cast that keeps state between calls will leak it, and a cast with a
required constructor parameter raises `ArgumentCountError`.

Custom casts work identically on `ArgonautDTO` and `ArgonautImmutableDTO`:

```php
final class InvoiceSnapshotDTO extends ArgonautImmutableDTO
{
    #[CastWith(MoneyCast::class)]
    public readonly mixed $total;
}

(new InvoiceSnapshotDTO(['total' => 4250]))->total;  // '$42.50'
```

## Key mapping

Incoming keys can be renamed to property names before anything else runs,
either with a `$maps` array or with a `#[MapFrom]` attribute on the property:

```php
use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\MapFrom;

final class ProfileDTO extends ArgonautDTO
{
    protected array $maps = ['first_name' => 'firstName'];

    public string $firstName = '';

    #[MapFrom('last_name')]
    public string $lastName = '';
}

$profile = new ProfileDTO(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

$profile->firstName; // 'Ada'
$profile->lastName;  // 'Lovelace'
```

Both forms can be used on the same class. Where both describe the same
**target property**, **`$maps` wins** and the `#[MapFrom]` attribute for that
property is dropped entirely — even when the two forms name different
incoming keys:

```php
final class ConflictingDTO extends ArgonautDTO
{
    // $maps wins: the #[MapFrom] attribute on $name below is dropped
    // entirely, even though 'legacy_name' and 'name_field' are different
    // incoming keys.
    protected array $maps = ['legacy_name' => 'name'];

    #[MapFrom('name_field')]
    public string $name = '';
}

$dto = new ConflictingDTO(['legacy_name' => 'Ada', 'name_field' => 'ignored']);

$dto->name; // 'Ada' — 'name_field' is no longer a recognized mapping, so it
            // is just an unknown input key and is ignored.
```

Key mapping runs before anything else that processes input — before the
`$prioritizedAttributes` pass and before casting. That applies to every input
path: the constructor, `setAttributes()`, `merge()`, `with()`, and the
single-key `setAttribute()`, which accepts either the incoming key or the
property name. A `$casts` entry (or cast attribute) for a mapped property is
therefore keyed by the **property name**, not the incoming key:

```php
final class AmountDTO extends ArgonautDTO
{
    #[MapFrom('unit_price')]
    public int $price = 0;

    // Casting looks up 'price' — the property name — not 'unit_price', the
    // incoming key, because mapping already ran and renamed the key.
    protected array $casts = ['price' => 'integer'];
}

$amount = new AmountDTO(['unit_price' => '4200']);

$amount->price; // 4200 (int)
```

If both a mapped key and its target property name appear in the same input
array, whichever occurs **later** in the array wins.

`with()` on `ArgonautImmutableDTO` does not rely on that rule. It maps the
incoming keys to work out which properties are changing, copies every
*unchanged* property to the new instance verbatim, and routes only the given
attributes through the normal input path. Unchanged values are never re-cast:
a full-state rebuild would run the casting engine over already-cast values,
and while a built-in cast survives that on its identity guard, a custom cast
is a transformation and would apply twice. Mapped keys work in `with()` for
the same reason they work anywhere else — they are mapped on the way in:

```php
use YorCreative\ArgonautDTO\ArgonautImmutableDTO;

final class ContactDTO extends ArgonautImmutableDTO
{
    protected array $maps = ['email_address' => 'email'];

    public readonly string $email;
}

$contact = new ContactDTO(['email_address' => 'ada@example.com']);
$contact->email; // 'ada@example.com'

$updated = $contact->with(['email_address' => 'lovelace@example.com']);
$updated->email; // 'lovelace@example.com'
```

Two output methods reverse the mapping: `toMappedArray(?int $depth = null)`
and `toMappedJson(int $options = 0, ?int $depth = null)` rename each top-level
key back to its incoming key. `toArray()` and `toJson()` are **unchanged** —
they still emit property names:

```php
final class PersonDTO extends ArgonautDTO
{
    protected array $maps = ['first_name' => 'firstName'];

    public string $firstName = '';
}

$person = new PersonDTO(['first_name' => 'Grace']);

$person->toArray();       // ['firstName' => 'Grace']
$person->toJson();        // '{"firstName":"Grace"}'
$person->toMappedArray(); // ['first_name' => 'Grace']
$person->toMappedJson();  // '{"first_name":"Grace"}'
```

`toMappedJson()` reports encoding failures exactly as `toJson()` does — both
delegate to the same internal encoder.

**Renaming onto an existing property throws.** If a mapping renames one
property onto the name of another property that also serializes, both land on
the same output key and one value would be lost. `toMappedArray()` and
`toMappedJson()` raise `LogicException` instead of dropping it:

```php
final class AmbiguousDTO extends ArgonautDTO
{
    // fullName is renamed to 'name' on output, but 'name' is a property too.
    protected array $maps = ['name' => 'fullName'];

    public ?string $fullName = null;
    public ?string $name = null;
}

(new AmbiguousDTO(['fullName' => 'Ada']))->toMappedArray();
// LogicException: two keys collide on 'name'
```

`toArray()` is unaffected — it emits property names, which are unique by
construction.

**Two properties cannot claim the same incoming key.** Declaring
`#[MapFrom('key')]` on more than one property is ambiguous — only one could
receive the value, and which one would depend on reflection order — so it
throws `LogicException` when the map is first built.

**Key mapping is top-level only.** `toMappedArray()` renames this DTO's own
keys; it does not reach into nested DTOs and rename their keys too, even if
the nested class declares its own `$maps` or `#[MapFrom]`:

```php
final class AddressDTO extends ArgonautDTO
{
    protected array $maps = ['zip_code' => 'zip'];

    public string $zip = '';
}

final class CustomerDTO extends ArgonautDTO
{
    protected array $casts = ['address' => AddressDTO::class];

    public ?AddressDTO $address = null;
}

$customer = new CustomerDTO(['address' => ['zip_code' => '10001']]);

$customer->toArray();
// ['address' => ['zip' => '10001']]

$customer->toMappedArray();
// ['address' => ['zip' => '10001']] — identical. toMappedArray() only
// renames CustomerDTO's own top-level keys ('address' has no mapping here,
// so it keeps its name). By the time it runs, toArray() has already
// flattened $address into a plain array — AddressDTO's own $maps has no
// object left to apply to.
```

This is inherent to how `toArray()` is used: it flattens the whole DTO graph
into plain arrays before `toMappedArray()` ever sees it, and `toArray()`
itself cannot be changed to preserve nested objects instead, because it is
declared on `ArgonautDTOContract`, which consumers implement directly. A
consumer with nested DTOs should expect only the outermost keys to be
renamed.

## Serialization depth

`toArray()`, `toJson()`, and `jsonSerialize()` walk the whole DTO graph:

```php
$dto->toArray();          // full graph
$dto->toArray(2);         // two levels of nested DTOs
$dto->toJson(depth: 2);   // same limit, JSON encoded
```

`$depth` counts DTO nesting levels and defaults to `ArgonautDTO::DEFAULT_MAX_DEPTH` (512). Exceeding
it throws a `RuntimeException` rather than silently emitting an empty array, so truncation can never
be mistaken for missing data.

Circular references are detected directly, not inferred from the depth limit, and raise
`YorCreative\ArgonautDTO\CircularReferenceException` (a `RuntimeException`) naming the instance
involved. The same DTO appearing in two sibling branches is a shared reference rather than a cycle
and serializes normally.

`toJson()` also accounts for PHP's own `json_encode()` depth limit. Because each array or collection
of DTOs adds a level of its own, a graph well inside `$depth` can still exceed the encoder's 512
levels; `toJson()` raises the encoder limit to fit whatever the walk produced. Note that
`json_decode()` has the same 512 default, so decoding very deep payloads needs an explicit depth.

## Collection

`Collection` is a small, dependency-free collection returned by
`collection:` casts and by `collection()`. It implements `ArrayAccess`,
`Countable`, `IteratorAggregate` and `JsonSerializable`.

It is generic over its value type, so static analysis narrows elements:

```php
/** @var Collection<TagDTO> */
public Collection $tags;

$this->tags->first()->name; // PHPStan resolves this to TagDTO::$name
```

Available methods:

| Method | Returns | Notes |
| --- | --- | --- |
| `all()` | `array` | Underlying items, keys preserved |
| `map(callable)` | `Collection` | Preserves keys |
| `filter(?callable)` | `Collection` | Callback receives value and key |
| `first(mixed $default = null)` | `TValue\|null` | |
| `last(mixed $default = null)` | `TValue\|null` | |
| `reduce(callable, mixed $initial = null)` | `mixed` | Callback receives carry, value, key |
| `contains(mixed)` | `bool` | A value compared strictly, or a predicate |
| `pluck(string $value, ?string $key = null)` | `Collection` | Reads array keys, `ArrayAccess` offsets, or object properties |
| `groupBy(callable)` | `Collection` | A `Collection` of `Collection`s |
| `keyBy(callable)` | `Collection` | Later duplicates win |
| `values()` | `Collection` | Reindexes |
| `isEmpty()` / `isNotEmpty()` | `bool` | |
| `count()` | `int` | |
| `validateAll(bool $throw = true)` | `true\|array` | Validates every item; errors keyed by collection key |
| `isValidAll()` | `bool` | True when every item validates |

### Validating a collection of DTOs

```php
$emails = EmailDTO::collection([
    ['email' => 'ada@example.com'],
    ['email' => 'not-an-email'],
]);

$emails->isValidAll();          // false
$errors = $emails->validateAll(false);
// [1 => ['email' => ['The email must be a valid email address.']]]

$emails->validateAll();         // throws the failing item's ValidationException
```

Errors are keyed by the item's collection key, so string-keyed collections
report which item failed. With `$throw = true` the first failing item's own
`ValidationException` is raised — use `validateAll(false)` when you need to know
which index failed.

An item that is not an Argonaut DTO, or a DTO whose class declares no `rules()`,
is a programming error and raises `LogicException` rather than being reported as
invalid.

These mirror the names and common calling conventions of
`Illuminate\Support\Collection` so the API is familiar, but deliberately omit
Laravel's operator overloads — `contains()` takes a value or a predicate, not
`($key, $operator, $value)`.

One caveat worth knowing: `contains()` decides between "value" and "predicate"
by testing `is_callable()`, with one deliberate exception — **a string is
always a value**, never a predicate, so a collection of strings stays
searchable for one that happens to name a function (`contains('is_int')` looks
for the string). Every other callable form is invoked as a predicate: a
closure, `[$object, 'method']`, or an object with `__invoke()`. Predicates
receive `($item, $key)`, so a one-argument function such as `is_int(...)` is
not a valid predicate. A collection whose *items are themselves closures*
cannot be searched by value; `Illuminate\Support\Collection` has the same
limitation. Use `in_array($needle, $collection->all(), true)` if you need that.

`pluck()` reads array keys, `ArrayAccess` offsets, and **public** object
properties. A key that is simply absent yields `null`, as it does for arrays.
A property that exists but is not public throws `LogicException` rather than
yielding `null`, so a private field is never silently reported as empty and a
typo is never mistaken for one.

## Immutable DTOs

Declare DTO properties as `readonly` and extend `ArgonautImmutableDTO`:

```php
final class UserSnapshotDTO extends ArgonautImmutableDTO
{
    public readonly string $id;
    public readonly string $name;
}
```

Readonly properties are initialized once during construction. Missing required properties remain uninitialized, allowing PHP's normal typed-property error to identify an incomplete snapshot.

## Copying with changes

`with()` returns a copy with the given attributes applied, leaving the original
untouched. It is available on both base classes.

```php
$original = new UserDTO(['firstName' => 'Jane', 'lastName' => 'Doe']);
$updated  = $original->with(['lastName' => 'Smith']);

$updated->lastName;   // 'Smith'
$original->lastName;  // 'Doe' — unchanged
```

Only the attributes you pass are re-applied, so setter-derived properties
recompute from the new values rather than being overwritten with stale ones:

```php
class UserDTO extends ArgonautDTO
{
    public string $firstName = '';

    public string $lastName = '';

    public string $fullName = '';

    /** @var list<string> */
    protected array $prioritizedAttributes = ['firstName', 'lastName'];

    public function setFirstName(string $value): static
    {
        $this->firstName = $value;
        $this->fullName = trim($value.' '.$this->lastName);

        return $this;
    }

    public function setLastName(string $value): static
    {
        $this->lastName = $value;
        $this->fullName = trim($this->firstName.' '.$value);

        return $this;
    }
}

$original = new UserDTO(['firstName' => 'Jane', 'lastName' => 'Doe']);

$original->with(['lastName' => 'Smith'])->fullName;  // 'Jane Smith'
```

`with()` is a **shallow copy** — nested DTOs and other objects are shared with
the original, not duplicated. Because `ArgonautDTO` is mutable, that means
mutating a nested DTO reached through the copy also mutates the original:

```php
class OrderDTO extends ArgonautDTO
{
    public ?TagDTO $tag = null;

    /** @var array<string, string> */
    protected array $casts = ['tag' => TagDTO::class];
}

$original = new OrderDTO(['tag' => ['name' => 'draft']]);
$copy = $original->with([]);

$copy->tag->name = 'final';

$original->tag->name;   // 'final' — the nested DTO is shared, not copied
```

Rebuild nested values explicitly if you need them independent.

## Assemblers

Assemblers resolve `to<ClassName>` first, then `from<ClassName>`:

```php
final class UserAssembler extends ArgonautAssembler
{
    public static function toUserDTO(object $input): UserDTO
    {
        return new UserDTO([
            'name' => $input->display_name,
            'email' => $input->email,
        ]);
    }
}

$user = UserAssembler::assemble($payload, UserDTO::class);
$users = UserAssembler::fromArray($payloads, UserDTO::class);
```

`fromArray()` and `fromCollection()` return the package's dependency-free `Collection`, which supports iteration, array access, `count()`, `first()`, `all()`, `map()`, and `filter()`.

Instance assembler methods are supported through `assembleInstance()` or by passing an assembler instance to `assemble()`.

## Validation

`rules()` uses [YorCreative DataValidation](https://packagist.org/packages/yorcreative/data-validation), including its complete rule set, nested paths, wildcards, and custom closures. Argonaut preserves the convenient `int`, `bool`, `collection`, and `sometimes` aliases: collections are arrays after serialization, and `sometimes` omits the rule set when the field is absent.

Custom closures use DataValidation's signature: `function (string $field, mixed $value, callable $fail, array $data): bool`.

```php
$errors = $user->validate(throw: false);
// ['email' => ['The email field failed the email rule.']]
```

Calling `validate()` without `throw: false` raises `YorCreative\ArgonautDTO\ValidationException`.

`isValid()` returns `false` only for validation failure. A missing or broken `rules()` method is a
programming error and surfaces as an exception rather than being reported as invalid data. Pass
`isValid(throw: true)` to raise `ValidationException` instead of returning `false`.

## Relationship to the Laravel package

`yorcreative/argonaut-dto` contains no Illuminate dependency. The Laravel package can provide Laravel-specific adapters and continue to expose its existing API while sharing this framework-neutral behavior. Laravel applications that need Laravel's validator or collection implementation can remain on `yorcreative/laravel-argonaut-dto`.

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage report:

```bash
composer coverage
```

Run static analysis (PHPStan):

```bash
composer phpstan
```

Run code style fixer (Pint):

```bash
composer lint
```

Continuous integration runs the suite on PHP 8.3, 8.4, and 8.5 against locked, lowest, and highest dependency sets,
alongside PHPStan, Pint, Composer validation, GitHub dependency review, and scheduled Composer security audits.

## Credits

- [Yorda](https://github.com/yordadev)
- [All Contributors](../../contributors)

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
