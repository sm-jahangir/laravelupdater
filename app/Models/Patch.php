<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
/**
 * Patch Model
 *
 * Represents a file that has been modified and needs to be included in a patch.
 *
 * @property int $id
 * @property string $file_from Relative path of the source file
 * @property \Carbon\Carbon|null $modified_at When the file was last modified
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Patch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_from',
        'modified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'modified_at' => 'datetime',
    ];
}
