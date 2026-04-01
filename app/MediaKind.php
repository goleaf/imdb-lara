<?php

namespace App;

enum MediaKind: string
{
    case Poster = 'poster';
    case Backdrop = 'backdrop';
    case Gallery = 'gallery';
    case Headshot = 'headshot';
    case Trailer = 'trailer';
}
