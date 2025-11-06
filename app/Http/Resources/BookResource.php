<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Book $book */
        $book = $this;

        // Build download links (mime-type + url)
        $links = $book->formats->map(function ($f) {
            return [
                'mime_type' => $f->mime_type,
                'url' => $f->url,
            ];
        })->unique(function ($item) {
            return $item['url'] . '|' . $item['mime_type'];
        })->values();

        // Languages list
        $languages = $book->languages->pluck('code')->unique()->values();

        // Subjects and bookshelves
        $subjects = $book->subjects->pluck('name')->unique()->values();
        $bookshelves = $book->bookshelves->pluck('name')->unique()->values();

        // Genre mapping: pick first bookshelf name (or null)
        $genre = $bookshelves->first() ?? null;

        return [
            'title' => $book->title,
            'authors' => $book->authors->map(function ($a) {
                return [
                    'name' => $a->name,
                    'birth_year' => $a->birth_year,
                    'death_year' => $a->death_year,
                ];
            })->values(),
            'genre' => $genre,
            'languages' => $languages,
            'subjects' => $subjects,
            'bookshelves' => $bookshelves,
            'links' => $links,
        ];
    }
}

