<?php

namespace YorCreative\ArgonautDTO\Tests\Fixtures;

use YorCreative\ArgonautDTO\ArgonautDTO;

/**
 * The slug setter depends on the title already being set, so `title` must be
 * prioritized regardless of input ordering.
 */
class SluggedDTO extends ArgonautDTO
{
    public string $title = '';

    public string $slug = '';

    /** @var list<string> */
    protected array $prioritizedAttributes = ['title'];

    public function setTitle(string $value): void
    {
        $this->title = $value;
    }

    public function setSlug(string $value): void
    {
        $this->slug = strtolower(str_replace(' ', '-', $this->title)).'-'.$value;
    }
}
