<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the document_sequences table.
 *
 * This model is intentionally thin — all business logic lives in
 * App\Services\NumberGenerator.  The model exists so that lockForUpdate()
 * queries have a proper Eloquent representation and so that the table can be
 * referenced cleanly from factories / seeders during testing.
 *
 * @property int         $id
 * @property int         $business_id
 * @property string      $document_type  sale|invoice|purchase|transfer
 * @property \Carbon\Carbon $sequence_date
 * @property int         $last_number
 * @property int|null    $branch_id
 */
class DocumentSequence extends Model
{
    protected $table = 'document_sequences';

    protected $fillable = [
        'business_id',
        'document_type',
        'sequence_date',
        'last_number',
        'branch_id',
    ];

    protected $casts = [
        'sequence_date' => 'date',
        'last_number'   => 'integer',
    ];
}
