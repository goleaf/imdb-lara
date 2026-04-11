<?php

namespace App\Enums;

enum AkaAttributeValue: string
{
    case ThreeDVersion = '3-D version';
    case AlternativeSpelling = 'alternative spelling';
    case AlternativeTransliteration = 'alternative transliteration';
    case AlternativeTitle = 'alternativeTitle';
    case CableTvTitle = 'cable TV title';
    case CompleteTitle = 'complete title';
    case DubbedVersion = 'dubbed version';
    case DvdTitle = 'dvdTitle';
    case FakeWorkingTitle = 'fake working title';
    case FirstSeasonTitle = 'first season title';
    case ImaxVersion = 'IMAX version';
    case InformalTitle = 'informal title';
    case LiteralEnglishTitle = 'literal English title';
    case LiteralTitle = 'literal title';
    case LongTitle = 'long title';
    case NewTitle = 'new title';
    case OriginalSubtitledVersion = 'original subtitled version';
    case OrthographicallyCorrectTitle = 'orthographically correct title';
    case PosterTitle = 'poster title';
    case PremiereTitle = 'premiere title';
    case PromotionalAbbreviation = 'promotional abbreviation';
    case ShortTitle = 'short title';
    case Subtitle = 'subtitle';
    case TransliteratedTitle = 'transliterated title';
    case TvTitle = 'tvTitle';
    case VideoBoxTitle = 'video box title';
    case WorkingTitle = 'workingTitle';

    public function label(): string
    {
        return match ($this) {
            self::ThreeDVersion => '3D version',
            self::AlternativeSpelling => 'Alternative spelling',
            self::AlternativeTransliteration => 'Alternative transliteration',
            self::AlternativeTitle => 'Alternative title',
            self::CableTvTitle => 'Cable TV title',
            self::CompleteTitle => 'Complete title',
            self::DubbedVersion => 'Dubbed version',
            self::DvdTitle => 'DVD title',
            self::FakeWorkingTitle => 'Fake working title',
            self::FirstSeasonTitle => 'First season title',
            self::ImaxVersion => 'IMAX version',
            self::InformalTitle => 'Informal title',
            self::LiteralEnglishTitle => 'Literal English title',
            self::LiteralTitle => 'Literal title',
            self::LongTitle => 'Long title',
            self::NewTitle => 'New title',
            self::OriginalSubtitledVersion => 'Original subtitled version',
            self::OrthographicallyCorrectTitle => 'Orthographically correct title',
            self::PosterTitle => 'Poster title',
            self::PremiereTitle => 'Premiere title',
            self::PromotionalAbbreviation => 'Promotional abbreviation',
            self::ShortTitle => 'Short title',
            self::Subtitle => 'Subtitle',
            self::TransliteratedTitle => 'Transliterated title',
            self::TvTitle => 'TV title',
            self::VideoBoxTitle => 'Video box title',
            self::WorkingTitle => 'Working title',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ThreeDVersion => 'Marks alternate titles used for 3D exhibition or packaging.',
            self::AlternativeSpelling => 'Shows a spelling variant used for the same release title.',
            self::AlternativeTransliteration => 'Captures a transliterated variant that differs from the main romanization.',
            self::AlternativeTitle => 'Flags a generally used alternate market title for the same movie or show.',
            self::CableTvTitle => 'Used for cable television listings or cable-network scheduling.',
            self::CompleteTitle => 'Represents a fuller version of the displayed title.',
            self::DubbedVersion => 'Used when a dubbed-language release carries a different displayed title.',
            self::DvdTitle => 'Used on DVD packaging or DVD storefront listings.',
            self::FakeWorkingTitle => 'A provisional or placeholder working title that was not the final public release name.',
            self::FirstSeasonTitle => 'Used specifically for first-season packaging or listing.',
            self::ImaxVersion => 'Used for IMAX-branded releases or marketing.',
            self::InformalTitle => 'An unofficial or colloquial alternate title used by audiences or local listings.',
            self::LiteralEnglishTitle => 'A direct English translation of the original title wording.',
            self::LiteralTitle => 'A literal translation of the original title wording.',
            self::LongTitle => 'A longer display variant of the release title.',
            self::NewTitle => 'An updated or retitled release name used after the original listing.',
            self::OriginalSubtitledVersion => 'Used when the original-language version is marketed with subtitles.',
            self::OrthographicallyCorrectTitle => 'A spelling-corrected version of the title for formal catalog use.',
            self::PosterTitle => 'The title variant printed on posters or key art.',
            self::PremiereTitle => 'Used for premiere screenings or debut-event listings.',
            self::PromotionalAbbreviation => 'A shortened promotional form used in marketing or campaigns.',
            self::ShortTitle => 'A shorter display title used in listings or packaging.',
            self::Subtitle => 'A subtitle-bearing variant of the primary release title.',
            self::TransliteratedTitle => 'A romanized or transliterated title used for another script.',
            self::TvTitle => 'Used for television listings or broadcast packaging.',
            self::VideoBoxTitle => 'The title variant printed on physical video packaging.',
            self::WorkingTitle => 'A working title used during development or production.',
        };
    }

    public static function fromValue(?string $value): ?self
    {
        if (! filled($value)) {
            return null;
        }

        return self::tryFrom((string) $value);
    }

    public static function labelFor(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return self::fromValue($value)?->label() ?? (string) $value;
    }

    public static function descriptionFor(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return self::fromValue($value)?->description() ?? 'Alternate-title marker attached to imported AKA records.';
    }
}
