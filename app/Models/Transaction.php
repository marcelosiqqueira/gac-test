<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TransactionType; // Importe o Enum

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'related_wallet_id',
        'type',
        'amount',
        'description',
        'is_reversal',
        'original_transaction_id',
    ];

    protected $appends = ['is_effectively_reversed'];

    public function getIsEffectivelyReversedAttribute(): bool
    {
        return $this->is_reversal || $this->reversalTransaction()->exists();
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => TransactionType::class, // Cast o campo 'type' para o Enum TransactionType
        'is_reversal' => 'boolean',
    ];

    /**
     * Get the wallet that this transaction belongs to.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the related wallet for this transaction (for transfers).
     */
    public function relatedWallet()
    {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    /**
     * Get the original transaction if this is a reversal.
     */
    public function originalTransaction()
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    /**
     * Get the reversal transaction if this is the original transaction.
     */
    public function reversalTransaction()
    {
        return $this->hasOne(Transaction::class, 'original_transaction_id');
    }
}
