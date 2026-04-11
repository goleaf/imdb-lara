<?php

namespace App\Enums;

enum LanguageCode: string
{
    case AmericanSignLanguage = 'ase';
    case Bengali = 'ben';
    case Bosnian = 'bos';
    case Bulgarian = 'bul';
    case Catalan = 'cat';
    case MandarinChinese = 'cmn';
    case German = 'deu';
    case English = 'eng';
    case Persian = 'fas';
    case French = 'fra';
    case Irish = 'gle';
    case Hebrew = 'heb';
    case Hindi = 'hin';
    case Croatian = 'hrv';
    case Indonesian = 'ind';
    case Italian = 'ita';
    case Japanese = 'jpn';
    case Korean = 'kor';
    case Malay = 'msa';
    case Dutch = 'nld';
    case Norwegian = 'nor';
    case Romanian = 'ron';
    case Russian = 'rus';
    case Spanish = 'spa';
    case Serbian = 'srp';
    case Swedish = 'swe';
    case Tamil = 'tam';
    case Thai = 'tha';
    case Turkish = 'tur';
    case Ukrainian = 'ukr';
    case Yiddish = 'yid';
    case YueChinese = 'yue';
    case Chinese = 'zho';

    public function label(): string
    {
        return match ($this) {
            self::AmericanSignLanguage => 'American Sign Language',
            self::Bengali => 'Bengali',
            self::Bosnian => 'Bosnian',
            self::Bulgarian => 'Bulgarian',
            self::Catalan => 'Catalan',
            self::MandarinChinese => 'Mandarin Chinese',
            self::German => 'German',
            self::English => 'English',
            self::Persian => 'Persian',
            self::French => 'French',
            self::Irish => 'Irish',
            self::Hebrew => 'Hebrew',
            self::Hindi => 'Hindi',
            self::Croatian => 'Croatian',
            self::Indonesian => 'Indonesian',
            self::Italian => 'Italian',
            self::Japanese => 'Japanese',
            self::Korean => 'Korean',
            self::Malay => 'Malay',
            self::Dutch => 'Dutch',
            self::Norwegian => 'Norwegian',
            self::Romanian => 'Romanian',
            self::Russian => 'Russian',
            self::Spanish => 'Spanish',
            self::Serbian => 'Serbian',
            self::Swedish => 'Swedish',
            self::Tamil => 'Tamil',
            self::Thai => 'Thai',
            self::Turkish => 'Turkish',
            self::Ukrainian => 'Ukrainian',
            self::Yiddish => 'Yiddish',
            self::YueChinese => 'Yue Chinese',
            self::Chinese => 'Chinese',
        };
    }

    public static function labelFor(?string $code): ?string
    {
        if (! filled($code)) {
            return null;
        }

        return self::tryFrom(strtolower((string) $code))?->label();
    }
}
