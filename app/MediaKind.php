<?php

namespace App;

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
}
