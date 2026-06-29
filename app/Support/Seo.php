<?php

namespace App\Support;

final readonly class Seo
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $keywords = null,
        public ?string $canonical = null,
        public ?string $image = null,
        public bool $index = true,
        public bool $follow = true,
        public string $type = 'website',
        public string $twitterCard = 'summary_large_image',
        public ?string $locale = null,
    ) {}

    public static function defaults(): self
    {
        return self::make([]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function make(array $attributes = []): self
    {
        $config = config('seo');

        return new self(
            title: $attributes['title'] ?? $config['title'],
            description: $attributes['description'] ?? $config['description'],
            keywords: $attributes['keywords'] ?? $config['keywords'],
            canonical: $attributes['canonical'] ?? null,
            image: $attributes['image'] ?? $config['image'],
            index: $attributes['index'] ?? $config['robots_index'],
            follow: $attributes['follow'] ?? $config['robots_follow'],
            type: $attributes['type'] ?? $config['og_type'],
            twitterCard: $attributes['twitter_card'] ?? $config['twitter_card'],
            locale: $attributes['locale'] ?? str_replace('_', '-', (string) config('app.locale')),
        );
    }

    public function robots(): string
    {
        return ($this->index ? 'index' : 'noindex').', '.($this->follow ? 'follow' : 'nofollow');
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?? url()->current();
    }

    public function imageUrl(): ?string
    {
        if ($this->image === null || $this->image === '') {
            return null;
        }

        return str_starts_with($this->image, 'http')
            ? $this->image
            : asset($this->image);
    }

    public function siteName(): string
    {
        return (string) config('seo.site_name', config('app.name'));
    }
}
