<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'compte_id',
        'type',
        'montant',
        'statut',
        'date_operation',
        'destinataire_id',
        'description',
        'reference'
    ];

    protected $casts = [
        'date_operation' => 'datetime',
        'montant' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Relation avec le compte destinataire (pour les transferts)
     */
    public function destinataire()
    {
        return $this->belongsTo(Compte::class, 'destinataire_id');
    }

    /**
     * Scope pour les transactions réussies
     */
    public function scopeReussies($query)
    {
        return $query->where('statut', 'reussi');
    }

    /**
     * Scope pour les transactions d'un utilisateur
     */
    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Générer une référence unique pour la transaction
     */
    public static function genererReference(): string
    {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }
}
