<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum CertificateRatingValue: string
{
    case Banned = '(Banned)';
    case Age0 = '0';
    case Age0Plus = '0+';
    case Age10 = '10';
    case Age10HyphenAge12 = '10-12';
    case Age10HyphenAge12Pg = '10-12PG';
    case Age10Plus = '10+';
    case Age11 = '11';
    case Age12 = '12';
    case Age12Plus = '12+';
    case Rating12A = '12A';
    case Age13 = '13';
    case Age13Plus = '13+';
    case Rating13A = '13A';
    case Age14 = '14';
    case Age14Plus = '14+';
    case Rating14A = '14A';
    case Age15 = '15';
    case Age15Plus = '15+';
    case Rating15A = '15A';
    case Age16 = '16';
    case Age16AvecAvertissement = '16 avec avertissement';
    case Age16Plus = '16+';
    case Age17 = '17';
    case Age17Plus = '17+';
    case Age18 = '18';
    case Age18Plus = '18+';
    case Age18PlusR = '18+R';
    case Rating18A = '18A';
    case Rating18Tc = '18TC';
    case Age19 = '19';
    case Age20 = '20';
    case Age21Plus = '21+';
    case Age5 = '5';
    case Age6 = '6';
    case Age6Plus = '6+';
    case Rating6A = '6A';
    case Age7 = '7';
    case Age7HyphenAge9Pg = '7-9PG';
    case Age7SlashI = '7/i';
    case Age7Plus = '7+';
    case Age8 = '8';
    case Age8Plus = '8+';
    case Age9 = '9';
    case Age9Plus = '9+';
    case A = 'A';
    case AHyphenAge18 = 'A-18';
    case ADotGDot = 'A.G.';
    case ASlashI = 'A/i';
    case Aa = 'AA';
    case Al = 'AL';
    case All = 'All';
    case Approved = 'Approved';
    case Apt = 'Apt';
    case Atp = 'Atp';
    case B = 'B';
    case B15 = 'B15';
    case Btl = 'Btl';
    case C = 'C';
    case C13 = 'C13';
    case C16 = 'C16';
    case C18 = 'C18';
    case D = 'D';
    case F = 'F';
    case G = 'G';
    case I = 'I';
    case IDotMDotHyphenAge18 = 'I.M.-18';
    case Iia = 'IIA';
    case Iib = 'IIB';
    case Iii = 'III';
    case K = 'K';
    case KHyphenAge12 = 'K-12';
    case KHyphenAge13 = 'K-13';
    case KHyphenAge15 = 'K-15';
    case KHyphenAge16 = 'K-16';
    case KHyphenAge18 = 'K-18';
    case KHyphenAge7 = 'K-7';
    case KHyphenAge8 = 'K-8';
    case Kn = 'KN';
    case KtSlashEa = 'KT/EA';
    case L = 'L';
    case Livre = 'Livre';
    case M = 'M';
    case MSlashAge12 = 'M/12';
    case MSlashAge14 = 'M/14';
    case MSlashAge16 = 'M/16';
    case MSlashAge18 = 'M/18';
    case MSlashAge4 = 'M/4';
    case MSlashAge6 = 'M/6';
    case M18 = 'M18';
    case Ma15Plus = 'MA15+';
    case Mg6 = 'MG6';
    case NHyphenAge13 = 'N-13';
    case NHyphenAge15 = 'N-15';
    case NHyphenAge16 = 'N-16';
    case NHyphenAge18 = 'N-18';
    case NHyphenAge7 = 'N-7';
    case Nc = 'NC';
    case NcHyphenAge16 = 'NC-16';
    case Nc16 = 'NC16';
    case NotRated = 'Not Rated';
    case P = 'P';
    case P12 = 'P12';
    case P13 = 'P13';
    case P16 = 'P16';
    case Pg = 'PG';
    case PgHyphenAge13 = 'PG-13';
    case PgHyphenAge15 = 'PG-15';
    case Pg12 = 'PG12';
    case Pg13 = 'PG13';
    case Pg15 = 'PG15';
    case Pg16 = 'PG16';
    case R = 'R';
    case RHyphenAge13 = 'R-13';
    case RHyphenAge16 = 'R-16';
    case RHyphenAge18 = 'R-18';
    case R12 = 'R12';
    case R13 = 'R13';
    case R15 = 'R15';
    case R15Plus = 'R15+';
    case R16 = 'R16';
    case R18 = 'R18';
    case R18Plus = 'R18+';
    case R21 = 'R21';
    case S = 'S';
    case Semua = 'Semua';
    case SinCalificacion = 'Sin Calificación';
    case Spg = 'SPG';
    case Su = 'SU';
    case T = 'T';
    case T13 = 'T13';
    case T13Plus = 'T13+';
    case T16 = 'T16';
    case T18 = 'T18';
    case Te = 'TE';
    case TePlusAge7 = 'TE+7';
    case Todo = 'TODO';
    case TousPublics = 'Tous publics';
    case TousPublicsAvecAvertissement = 'Tous publics avec avertissement';
    case Tp = 'TP';
    case Tp12Plus = 'TP12+';
    case Tp7 = 'TP7';
    case TvHyphenAge14 = 'TV-14';
    case TvHyphenG = 'TV-G';
    case TvHyphenMa = 'TV-MA';
    case TvHyphenPg = 'TV-PG';
    case TvHyphenY = 'TV-Y';
    case TvHyphenY7 = 'TV-Y7';
    case TvHyphenY7HyphenFv = 'TV-Y7-FV';
    case U = 'U';
    case Ua = 'UA';
    case UaAge13Plus = 'UA 13+';
    case UaAge16Plus = 'UA 16+';
    case V = 'V';
    case Vm14 = 'VM14';
    case Vm16 = 'VM16';
    case Vm18 = 'VM18';
    case Za = 'ZA';

    public function label(): string
    {
        return $this->value;
    }

    public function description(): string
    {
        $normalized = $this->normalized();

        return match (true) {
            $this === self::Banned => 'Unavailable for general release in at least one market.',
            $this->isUnrated() => 'No public age classification is attached in this catalog.',
            $normalized === 'PG' => 'Parental guidance is suggested for younger viewers.',
            in_array($normalized, ['PG-13', 'PG13'], true) => 'Some material may be unsuitable for children under 13.',
            $normalized === 'R' => 'Restricted rating; younger viewers may need an accompanying adult.',
            $normalized === 'TV-G' => 'Television rating suitable for most audiences.',
            $normalized === 'TV-PG' => 'Television rating recommending parental guidance.',
            $normalized === 'TV-14' => 'Television rating usually recommended for viewers 14 and older.',
            $normalized === 'TV-MA' => 'Television rating intended for mature audiences only.',
            $normalized === 'TV-Y' => 'Television rating aimed at very young viewers.',
            $normalized === 'TV-Y7' => 'Television rating for children 7 and older.',
            $normalized === 'TV-Y7-FV' => 'Television rating for children 7 and older, with fantasy violence.',
            in_array($normalized, ['NC', 'NC-16', 'NC16'], true) => 'No-children classification; usually limited to older teens or adults.',
            $this->isAdultsOnly() => $this->ageReferenceDescription(
                'Adults-only classification',
                'Adults-only or strictly mature-audience classification in this market.',
            ),
            $this->requiresGuidance() && $this->ageThreshold() !== null => 'Adult guidance is recommended; '.$this->ageThreshold().' is the local reference age.',
            $this->requiresGuidance() => 'Parental guidance or adult discretion is recommended for younger viewers.',
            $this->isGeneralAudience() => 'Suitable for most audiences with little or no restriction.',
            $normalized === 'M' => 'Mature guidance rating; some material may not suit younger viewers.',
            $this->ageThreshold() !== null => $this->thresholdDescription(),
            default => 'Regional age classification attached to this title.',
        };
    }

    public function tone(): string
    {
        return match (true) {
            $this === self::Banned => 'banned',
            $this->isUnrated() => 'neutral',
            $this->isTelevision() => 'tv',
            $this->isAdultsOnly() => 'adult',
            $this->requiresGuidance() && ($this->ageThreshold() ?? 0) >= 15 => 'mature',
            $this->ageThreshold() !== null && $this->ageThreshold() >= 15 => 'mature',
            $this->ageThreshold() !== null && $this->ageThreshold() >= 12 => 'teen',
            $this->requiresGuidance() => 'advisory',
            $this->isGeneralAudience() => 'general',
            default => 'advisory',
        };
    }

    public function iconName(): string
    {
        return match ($this->tone()) {
            'banned' => 'ban',
            'general' => 'child-reaching',
            'advisory' => 'shield-halved',
            'teen' => 'triangle-exclamation',
            'mature' => 'circle-exclamation',
            'adult' => 'lock',
            'tv' => 'tv',
            default => 'circle-question',
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

        return self::fromValue($value)?->description() ?? 'Regional age classification attached to this title.';
    }

    public static function toneFor(?string $value): string
    {
        return self::fromValue($value)?->tone() ?? 'neutral';
    }

    public static function iconNameFor(?string $value): string
    {
        return self::fromValue($value)?->iconName() ?? 'circle-question';
    }

    private function ageThreshold(): ?int
    {
        $normalized = $this->normalized();

        foreach ([
            '/^TV-(?<age>\d+)$/',
            '/^(?<age>\d+)$/',
            '/^(?<age>\d+)\+$/',
            '/^(?<age>\d+)A$/',
            '/^(?<age>\d+)TC$/',
            '/^PG-?(?<age>\d+)$/',
            '/^P(?<age>\d+)$/',
            '/^R(?<age>\d+)\+?$/',
            '/^M\/(?<age>\d+)$/',
            '/^N-?(?<age>\d+)$/',
            '/^C(?<age>\d+)$/',
            '/^K-?(?<age>\d+)$/',
            '/^T(?<age>\d+)\+?$/',
            '/^VM(?<age>\d+)$/',
            '/^UA (?<age>\d+)\+?$/',
            '/^TP(?<age>\d+)\+?$/',
            '/^TE\+(?<age>\d+)$/',
            '/^(?<age>\d+)\/I$/',
            '/^(?<age>\d+)-\d+PG$/',
            '/^(?<age>\d+)-\d+$/',
        ] as $pattern) {
            if (preg_match($pattern, $normalized, $matches) === 1) {
                return (int) $matches['age'];
            }
        }

        return match ($normalized) {
            'MA15+', 'B15' => 15,
            'M18' => 18,
            'A-18', '18+R', 'I.M.-18' => 18,
            default => null,
        };
    }

    private function isAdultsOnly(): bool
    {
        $normalized = $this->normalized();

        if (in_array($normalized, ['TV-MA', 'A-18', '18+R', '18TC', 'NC', 'NC-16', 'NC16', 'I.M.-18'], true)) {
            return true;
        }

        if (preg_match('/^(18A|18TC|R18\+?|R21|C18|N-18|M18|M\/18|VM18)$/', $normalized) === 1) {
            return true;
        }

        return $this->ageThreshold() !== null && $this->ageThreshold() >= 18;
    }

    private function isGeneralAudience(): bool
    {
        $normalized = $this->normalized();

        if (in_array($normalized, ['G', 'U', 'ALL', 'APPROVED', 'SEMUA', 'TOUS PUBLICS', 'SU', 'L', 'LIVRE', 'ATP', 'APT'], true)) {
            return true;
        }

        return ! $this->requiresGuidance()
            && ! $this->isAdultsOnly()
            && ! $this->isTelevision()
            && ($this->ageThreshold() !== null && $this->ageThreshold() < 12);
    }

    private function isTelevision(): bool
    {
        return str_starts_with($this->normalized(), 'TV-');
    }

    private function isUnrated(): bool
    {
        return in_array($this->normalized(), ['NOT RATED', 'SIN CALIFICACION', 'TODO'], true);
    }

    private function requiresGuidance(): bool
    {
        $normalized = $this->normalized();

        if (preg_match('/PG|UA|TP|TE|SPG|AVERTISSEMENT/', $normalized) === 1) {
            return true;
        }

        if (preg_match('/^(\d+)A$/', $normalized) === 1) {
            return true;
        }

        return in_array($normalized, ['A', 'AA', 'AL', 'A.G.', 'A/I'], true);
    }

    private function thresholdDescription(): string
    {
        $age = $this->ageThreshold();

        if ($age === null) {
            return 'Regional age classification attached to this title.';
        }

        return match (true) {
            $age >= 18 => 'Usually limited to viewers aged '.$age.' and older.',
            $age >= 15 => 'Usually limited to older teens aged '.$age.' and up.',
            $age >= 12 => 'Usually aimed at viewers aged '.$age.' and older.',
            $age >= 7 => 'Usually suitable for children aged '.$age.' and older.',
            default => 'Usually suitable for very young viewers.',
        };
    }

    private function ageReferenceDescription(string $prefix, string $fallback): string
    {
        $age = $this->ageThreshold();

        if ($age === null) {
            return $fallback;
        }

        return $prefix.'; '.$age.' is the local reference age.';
    }

    private function normalized(): string
    {
        return Str::of(Str::ascii($this->value))
            ->upper()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }
}
