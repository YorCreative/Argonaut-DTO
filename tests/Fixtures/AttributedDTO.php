<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;
use YorCreative\ArgonautDTO\Attributes\CastCollection;
use YorCreative\ArgonautDTO\Attributes\CastEnum;
use YorCreative\ArgonautDTO\Attributes\CastTo;
use YorCreative\ArgonautDTO\Collection;

class AttributedDTO extends ArgonautDTO
{
    #[CastTo(TagDTO::class)]
    public ?TagDTO $primaryTag = null;

    /** @var array<int, TagDTO> */
    #[CastTo(TagDTO::class, many: true)]
    public array $tags = [];

    /** @var Collection<TagDTO> */
    #[CastCollection(TagDTO::class)]
    public ?Collection $tagCollection = null;

    #[CastEnum(Status::class)]
    public ?Status $status = null;
}
