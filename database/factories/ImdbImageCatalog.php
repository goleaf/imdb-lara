<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use InvalidArgumentException;

class ImdbImageCatalog
{
    /**
     * @var list<string>
     */
    private const TITLE_POSTERS = [
        'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTExMzU0ODcxNDheQTJeQWpwZ15BbWU4MDE1OTI4MzAy._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMjA5NjM3NTk1M15BMl5BanBnXkFtZTgwMzg1MzU2NjE@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BZDRkODJhOTgtOTc1OC00NTgzLTk4NjItNDgxZDY4YjlmNDY2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BNzA1Njg4NzYxOV5BMl5BanBnXkFtZTgwODk5NjU3MzI@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BNWIyNmU5MGYtZDZmNi00ZjAwLWJlYjgtZTc0ZGIxMDE4ZGYwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_FMjpg_UX1000_.jpg',
    ];

    /**
     * @var list<string>
     */
    private const TITLE_BACKDROPS = [
        'https://m.media-amazon.com/images/M/MV5BMTQ1ZmIzOTAtNDcwZi00NDVkLWE4NWItYWNhZGY1MmVlZGU0XkEyXkFqcGdeQWRvb2xpbmhk._V1_QL75_UY281_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BNjM5OTQzMTg4N15BMl5BanBnXkFtZTgwOTgyMjM0NTE@._V1_QL75_UX500_CR0,46,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BNzA4Mzg5OTY5Nl5BMl5BanBnXkFtZTgwMDk4ODg0MDI@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMjk0MTYzODAzNF5BMl5BanBnXkFtZTgwNDg0ODc4NjE@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTU4MzkxNTU0OF5BMl5BanBnXkFtZTgwNzQzMTc1NTE@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BN2NjZjUwM2EtNjc2Yy00NzRkLTg3ODctNTgyYzE1ZjUxN2FhXkEyXkFqcGdeQWpvaG5oYXJ0._V1_QL75_UY281_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BOTEwYWFjYmItZWJmNi00MGExLWI1MjktYzRiYjJkNzhiMWIxXkEyXkFqcGdeQXNuZXNodQ@@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BNWJkYWJlOWMtY2ZhZi00YWM0LTliZDktYmRiMGYwNzczMTZhXkEyXkFqcGdeQXVyNzU1NzE3NTg@._V1_QL75_UX500_CR0,47,500,281_.jpg',
    ];

    /**
     * @var list<string>
     */
    private const PERSON_HEADSHOTS = [
        'https://m.media-amazon.com/images/M/MV5BMjI0MTg3MzI0M15BMl5BanBnXkFtZTcwMzQyODU2Mw@@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTU1MDM5NjczOF5BMl5BanBnXkFtZTcwOTY2MDE4OA@@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMzU2MDk5MDI2MF5BMl5BanBnXkFtZTcwNDkwMjMzNA@@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTUxNDY4MTMzM15BMl5BanBnXkFtZTcwMjg5NzM2Ng@@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTQ3ODEyNjA4Nl5BMl5BanBnXkFtZTgwMTE4ODMyMjE@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTU0NTc4MzEyOV5BMl5BanBnXkFtZTcwODY0ODkzMQ@@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BZjM5N2U3MzQtZWU5My00YzE0LThmZTgtYjE1NDJjNmIzZmIxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTQzMjkwNTQ2OF5BMl5BanBnXkFtZTgwNTQ4MTQ4MTE@._V1_FMjpg_UX1000_.jpg',
    ];

    /**
     * @var list<string>
     */
    private const PERSON_GALLERIES = [
        'https://m.media-amazon.com/images/M/MV5BZmI2YzQzZDEtMTJkMS00NmNiLTk2NWYtYmQzYTc2ZmMxZGM1XkEyXkFqcGdeQXJoYW5uYWg@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BY2JiM2Y4NWYtMWUwYi00YmM0LWEyZjQtN2NiM2QyZmU3MGJhXkEyXkFqcGdeQXJoYW5uYWg@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMTQyOTA5N2MtMGE0NC00NmJmLThiOTItY2E3ZjIwMjE2OWQ0XkEyXkFqcGdeQWFsZWxvZw@@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMzhlMjg5MDItYzE0OC00NjRjLWIxYjYtMDkyZTliZGMxNmFlXkEyXkFqcGdeQXJoYW5uYWg@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BODYyZWU2MDctMzM4NC00YzBhLTkwZDktOGEzYmMzY2RkMTJmXkEyXkFqcGdeQWFsZWxvZw@@._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BZTNmNmQ2ZmItYzE0Yi00MDkwLTk4MGEtNzUyNjhjZTY1ODM0XkEyXkFqcGdeQW1hZGV0aXNj._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMGVmNWUzMmYtZDRmOS00NGE2LThmYTItMGQzNGQxMGFiNjQyXkEyXkFqcGdeQWpnYW1i._V1_QL75_UX500_CR0,0,500,281_.jpg',
        'https://m.media-amazon.com/images/M/MV5BMDJmMzZkOGEtZGY0ZC00ZjZmLWEzZmYtMzg4NDY3MGVmNDdlXkEyXkFqcGdeQWphZGRpc2w@._V1_QL75_UX500_CR0,0,500,281_.jpg',
    ];

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function titlePoster(int $index = 0): array
    {
        return self::payload(self::TITLE_POSTERS, $index, 1200, 1800);
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function titleBackdrop(int $index = 0): array
    {
        return self::payload(self::TITLE_BACKDROPS, $index, 1600, 900);
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function titleGallery(int $index = 0): array
    {
        return self::titleBackdrop($index);
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function titleStill(int $index = 0): array
    {
        return self::titleBackdrop($index);
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function personHeadshot(int $index = 0): array
    {
        return self::payload(self::PERSON_HEADSHOTS, $index, 900, 1200);
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function personGallery(int $index = 0): array
    {
        return self::payload(self::PERSON_GALLERIES, $index, 1600, 900);
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function titleImage(MediaKind $kind, int $index = 0): array
    {
        return match ($kind) {
            MediaKind::Poster => self::titlePoster($index),
            MediaKind::Backdrop => self::titleBackdrop($index),
            MediaKind::Gallery => self::titleGallery($index),
            MediaKind::Still => self::titleStill($index),
            default => throw new InvalidArgumentException(sprintf('Unsupported title image kind [%s].', $kind->value)),
        };
    }

    /**
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    public static function personImage(MediaKind $kind, int $index = 0): array
    {
        return match ($kind) {
            MediaKind::Headshot => self::personHeadshot($index),
            MediaKind::Gallery, MediaKind::Still => self::personGallery($index),
            default => throw new InvalidArgumentException(sprintf('Unsupported person image kind [%s].', $kind->value)),
        };
    }

    /**
     * @param  list<string>  $urls
     * @return array{url: string, width: int, height: int, provider: string, provider_key: string}
     */
    private static function payload(array $urls, int $index, int $width, int $height): array
    {
        $url = $urls[$index % count($urls)];

        return [
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'provider' => 'imdb',
            'provider_key' => self::providerKey($url),
        ];
    }

    private static function providerKey(string $url): string
    {
        preg_match('#/M/([^/]+?)\\._V1#', $url, $matches);

        return $matches[1] ?? $url;
    }
}
