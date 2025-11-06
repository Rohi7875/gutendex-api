<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Format extends Model
{
    protected $table = 'books_format';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['mime_type', 'url', 'book_id'];

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'id');
    }
}
