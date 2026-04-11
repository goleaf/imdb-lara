<?php

namespace App\Enums;

enum TitleMediaArchiveKind: string
{
    case Posters = 'posters';
    case Stills = 'stills';
    case Backdrops = 'backdrops';
    case Trailers = 'trailers';

    public function label(): string
    {
        return match ($this) {
            self::Posters => 'Posters',
            self::Stills => 'Stills',
            self::Backdrops => 'Backdrops',
            self::Trailers => 'Trailers',
        };
    }

    public function singularLabel(): string
    {
        return match ($this) {
            self::Posters => 'Poster',
            self::Stills => 'Still',
            self::Backdrops => 'Backdrop',
            self::Trailers => 'Trailer',
        };
    }

    public function badgeIcon(): string
    {
        return match ($this) {
            self::Posters, self::Backdrops => 'photo',
            self::Stills => 'rectangle-stack',
            self::Trailers => 'play',
        };
    }

    public function sectionId(): string
    {
        return 'title-media-'.$this->value;
    }

    public function archiveSectionId(): string
    {
        return 'title-media-archive-'.$this->value;
    }

    public function isImageArchive(): bool
    {
        return $this !== self::Trailers;
    }

    public function archiveDescription(): string
    {
        return match ($this) {
            self::Posters => 'The full poster archive for this title, including campaign one-sheets, key art, and primary cover variants.',
            self::Stills => 'A dedicated gallery of stills, scene captures, and editorial imagery attached to this title.',
            self::Backdrops => 'Wide-format backdrops and cinematic hero artwork selected from the attached media archive.',
            self::Trailers => 'Trailer, clip, and featurette records attached to this title, with direct IMDb video links and detailed archive metadata.',
        };
    }

    public function emptyHeading(): string
    {
        return match ($this) {
            self::Posters => 'No posters are published yet.',
            self::Stills => 'No stills are published yet.',
            self::Backdrops => 'No backdrops are published yet.',
            self::Trailers => 'No trailers are published yet.',
        };
    }

    public function emptyCopy(): ?string
    {
        return match ($this) {
            self::Posters => null,
            self::Stills => 'The archive is ready for production stills and gallery image drops as the media feed expands.',
            self::Backdrops => null,
            self::Trailers => 'Trailer links will appear here as soon as the public media feed is attached to this title.',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $archiveKind): string => $archiveKind->value,
            self::cases(),
        );
    }
}
