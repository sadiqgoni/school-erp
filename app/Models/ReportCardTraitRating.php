<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'report_card_id', 'result_trait_item_id', 'rating', 'remarks'])]
class ReportCardTraitRating extends Model
{
    use HasFactory;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function traitItem(): BelongsTo
    {
        return $this->belongsTo(ResultTraitItem::class, 'result_trait_item_id');
    }
}
