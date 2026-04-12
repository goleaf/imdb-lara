<?php

namespace App\Actions\Admin\Concerns;

use App\Models\Credit;
use App\Models\Genre;
use App\Models\LocalCredit;
use App\Models\LocalGenre;
use App\Models\LocalPerson;
use App\Models\LocalPersonProfession;
use App\Models\LocalTitle;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Title;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesLocalCatalogWriteModels
{
    protected function resolveLocalTitle(Title $title): LocalTitle
    {
        if ($title instanceof LocalTitle) {
            return $title;
        }

        $resolvedTitle = null;
        $key = $title->getKey();

        if ($key !== null) {
            $resolvedTitle = LocalTitle::query()->find($key);
        }

        if ($resolvedTitle === null && filled($title->slug)) {
            $resolvedTitle = LocalTitle::query()->where('slug', $title->slug)->first();
        }

        if ($resolvedTitle === null && filled($title->imdb_id)) {
            $resolvedTitle = LocalTitle::query()->where('imdb_id', $title->imdb_id)->first();
        }

        if ($resolvedTitle instanceof LocalTitle) {
            return $resolvedTitle;
        }

        throw (new ModelNotFoundException)->setModel(LocalTitle::class, [$key]);
    }

    protected function resolveLocalPerson(Person $person): LocalPerson
    {
        if ($person instanceof LocalPerson) {
            return $person;
        }

        $resolvedPerson = null;
        $key = $person->getKey();

        if ($key !== null) {
            $resolvedPerson = LocalPerson::query()->find($key);
        }

        if ($resolvedPerson === null && filled($person->slug)) {
            $resolvedPerson = LocalPerson::query()->where('slug', $person->slug)->first();
        }

        if ($resolvedPerson === null && filled($person->imdb_id)) {
            $resolvedPerson = LocalPerson::query()->where('imdb_id', $person->imdb_id)->first();
        }

        if ($resolvedPerson instanceof LocalPerson) {
            return $resolvedPerson;
        }

        throw (new ModelNotFoundException)->setModel(LocalPerson::class, [$key]);
    }

    protected function resolveLocalGenre(Genre $genre): LocalGenre
    {
        if ($genre instanceof LocalGenre) {
            return $genre;
        }

        $resolvedGenre = null;
        $key = $genre->getKey();

        if ($key !== null) {
            $resolvedGenre = LocalGenre::query()->find($key);
        }

        if ($resolvedGenre === null && filled($genre->slug)) {
            $resolvedGenre = LocalGenre::query()->where('slug', $genre->slug)->first();
        }

        if ($resolvedGenre === null && filled($genre->name)) {
            $resolvedGenre = LocalGenre::query()->where('name', $genre->name)->first();
        }

        if ($resolvedGenre instanceof LocalGenre) {
            return $resolvedGenre;
        }

        throw (new ModelNotFoundException)->setModel(LocalGenre::class, [$key]);
    }

    protected function resolveLocalCredit(Credit $credit): LocalCredit
    {
        if ($credit instanceof LocalCredit) {
            return $credit;
        }

        return LocalCredit::query()->findOrFail($credit->getKey());
    }

    protected function resolveLocalPersonProfession(PersonProfession $profession): LocalPersonProfession
    {
        if ($profession instanceof LocalPersonProfession) {
            return $profession;
        }

        return LocalPersonProfession::query()->findOrFail($profession->getKey());
    }
}
