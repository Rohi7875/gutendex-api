<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(Request $request)
    {
        // Allowed filters: gutenberg_id, language, mime_type, topic, author, title
        // Multiple values per filter are comma-separated.
        $q = Book::query()->from('books_book as b')->select('b.*');

        // joinable relations used for filtering
        // We'll use EXISTS subqueries to avoid duplicate rows caused by joins,
        // but we still eager-load relationships later.

        // Gutenberg IDs (project gutenberg ids)
        if ($request->filled('gutenberg_id')) {
            $ids = $this->explodeAndNormalize($request->query('gutenberg_id'));
            $q->whereIn('b.gutenberg_id', $ids);
        }

        // Language filter: languages.code IN (...)
        if ($request->filled('language')) {
            $codes = $this->explodeAndNormalize($request->query('language'));
            $q->whereExists(function ($sub) use ($codes) {
                $sub->select(DB::raw(1))
                    ->from('books_book_languages as bbl')
                    ->join('books_language as lang', 'bbl.language_id', '=', 'lang.id')
                    ->whereRaw('bbl.book_id = b.id')
                    ->whereIn('lang.code', $codes);
            });
        }

        // Mime-type filter: formats.mime_type IN (...)
        if ($request->filled('mime_type')) {
            $mimes = $this->explodeAndNormalize($request->query('mime_type'));
            $q->whereExists(function ($sub) use ($mimes) {
                $sub->select(DB::raw(1))
                    ->from('books_format as f')
                    ->whereRaw('f.book_id = b.id')
                    ->whereIn('f.mime_type', $mimes);
            });
        }

        // Topic filter: partial, case-insensitive match against bookshelf.name OR subject.name
        // support multiple topic values (OR across values, but AND combined with other criteria)
        if ($request->filled('topic')) {
            $topics = $this->explodeAndNormalize($request->query('topic'));
            $q->where(function ($outer) use ($topics) {
                foreach ($topics as $term) {
                    $termPattern = "%{$term}%";
                    $outer->where(function ($inner) use ($termPattern) {
                        // bookshelf match
                        $inner->whereExists(function ($sub) use ($termPattern) {
                            $sub->select(DB::raw(1))
                                ->from('books_book_bookshelves as bbb')
                                ->join('books_bookshelf as bs', 'bbb.bookshelf_id', '=', 'bs.id')
                                ->whereRaw('bbb.book_id = b.id')
                                ->where('bs.name', 'ILIKE', $termPattern);
                        })
                            // OR subject match
                            ->orWhereExists(function ($sub2) use ($termPattern) {
                                $sub2->select(DB::raw(1))
                                    ->from('books_book_subjects as bbs')
                                    ->join('books_subject as s', 'bbs.subject_id', '=', 's.id')
                                    ->whereRaw('bbs.book_id = b.id')
                                    ->where('s.name', 'ILIKE', $termPattern);
                            });
                    });
                }
            });
        }

        // Author partial match (case-insensitive)
        if ($request->filled('author')) {
            $authors = $this->explodeAndNormalize($request->query('author'));
            $q->where(function ($outer) use ($authors) {
                foreach ($authors as $term) {
                    $pattern = "%{$term}%";
                    $outer->whereExists(function ($sub) use ($pattern) {
                        $sub->select(DB::raw(1))
                            ->from('books_book_authors as bba')
                            ->join('books_author as a', 'bba.author_id', '=', 'a.id')
                            ->whereRaw('bba.book_id = b.id')
                            ->where('a.name', 'ILIKE', $pattern);
                    });
                }
            });
        }

        // Title partial match (case-insensitive)
        if ($request->filled('title')) {
            $titles = $this->explodeAndNormalize($request->query('title'));
            $q->where(function ($outer) use ($titles) {
                foreach ($titles as $term) {
                    $outer->where('b.title', 'ILIKE', "%{$term}%");
                }
            });
        }

        // Order by popularity (download_count desc). Nulls treated as 0.
        $q->orderByRaw("COALESCE(b.download_count, 0) DESC");

        // Total count (distinct by book id)
        $total = DB::table(DB::raw("({$q->toSql()}) as sub"))
            ->mergeBindings($q->getQuery()) // push bindings
            ->select(DB::raw('COUNT(*) as cnt'))
            ->value('cnt');

        // Pagination: limit 25 per page, page param default 1
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Get page results: fetch IDs first to ensure unique books
        // PostgreSQL requires ORDER BY columns to be in SELECT when using DISTINCT
        // Since we use EXISTS subqueries (not joins), duplicates shouldn't occur, but DISTINCT is kept as safety
        $idsQuery = clone $q;
        $ids = $idsQuery->select('b.id', DB::raw('COALESCE(b.download_count, 0) as download_count'))
            ->distinct()
            ->orderBy('download_count', 'desc') // Use the alias from SELECT
            ->limit($perPage)
            ->offset($offset)
            ->pluck('b.id')
            ->toArray();

        // If no results, return empty
        if (empty($ids)) {
            return response()->json([
                'total_count' => (int) $total,
                'books' => [],
            ]);
        }

        // Load books with eager relationships, maintaining order by download_count
        $books = Book::with(['authors', 'formats', 'bookshelves', 'languages', 'subjects'])
            ->whereIn('id', $ids)
            ->get()
            ->sortByDesc('download_count')
            ->values();

        // Format response with resource
        return response()->json([
            'total_count' => (int) $total,
            'books' => BookResource::collection($books)->resolve(), // array of formatted books
        ]);
    }

    /**
     * Explode comma-separated string to array of trimmed values; normalize to array.
     */
    protected function explodeAndNormalize(?string $str): array
    {
        if ($str === null) return [];
        $parts = array_filter(array_map('trim', explode(',', $str)), function ($v) {
            return $v !== '';
        });
        // For numeric-looking values, keep as integers
        return array_values(array_map(function ($v) {
            return is_numeric($v) ? (int)$v : $v;
        }, $parts));
    }
}
