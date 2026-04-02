<?php

namespace App\Enums;

use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Model;

enum MediaKind: string
{
    case Poster = 'poster';
    case Backdrop = 'backdrop';
    case Gallery = 'gallery';
    case Still = 'still';
    case Headshot = 'headshot';
    case Trailer = 'trailer';
    case Clip = 'clip';
    case Featurette = 'featurette';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }

    public function isImage(): bool
    {
        return in_array($this, self::imageKinds(), true);
    }

    public function isVideo(): bool
    {
        return in_array($this, self::videoKinds(), true);
    }

    /**
     * @return list<self>
     */
    public static function imageKinds(): array
    {
        return [
            self::Poster,
            self::Backdrop,
            self::Gallery,
            self::Still,
            self::Headshot,
        ];
    }

    /**
     * @return list<string>
     */
    public static function imageValues(): array
    {
        return array_map(
            static fn (self $kind): string => $kind->value,
            self::imageKinds(),
        );
    }

    /**
     * @return list<self>
     */
    public static function videoKinds(): array
    {
        return [
            self::Trailer,
            self::Clip,
            self::Featurette,
        ];
    }

    /**
     * @return list<string>
     */
    public static function videoValues(): array
    {
        return array_map(
            static fn (self $kind): string => $kind->value,
            self::videoKinds(),
        );
    }

    /**
     * @param  Model|class-string<Model>|null  $mediable
     * @return list<self>
     */
    public static function allowedForMediable(Model|string|null $mediable): array
    {
        $mediableClass = $mediable instanceof Model ? $mediable::class : $mediable;

        return match ($mediableClass) {
            Title::class => [
                self::Poster,
                self::Backdrop,
                self::Gallery,
                self::Still,
                self::Trailer,
                self::Clip,
                self::Featurette,
            ],
            Person::class => [
                self::Headshot,
                self::Gallery,
                self::Still,
            ],
            default => self::cases(),
        };
    }

    /**
     * @param  Model|class-string<Model>|null  $mediable
     * @return list<string>
     */
    public static function allowedValuesForMediable(Model|string|null $mediable): array
    {
        return array_map(
            static fn (self $kind): string => $kind->value,
            self::allowedForMediable($mediable),
        );
    }
}
