<?php

namespace App\Models;

use App\Models\Scopes\CompteScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';

    // Account types constants
    const TYPE_MARCHAND = 'marchand';
    const TYPE_SIMPLE = 'simple';

    // Available account types
    const TYPES = [
        self::TYPE_MARCHAND,
        self::TYPE_SIMPLE,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }

            // Generate code_marchand for marchand accounts
            if ($model->type === self::TYPE_MARCHAND && empty($model->code_marchand)) {
                $model->code_marchand = self::generateCodeMarchand();
            }
        });
    }

    protected $fillable = [
        'client_id',
        'numero_compte',
        'type',
        'statut',
        'motif_blocage',
        'date_fermeture',
        'code_marchand',
    ];

    protected $appends = ['solde'];

    /**
     * Les scopes globaux du modèle
     */
    protected static function booted()
    {
        static::addGlobalScope(new CompteScope());
    }

    // Les relations entre compte et les autres modèles (client, transactions)
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope pour récupérer un compte par numéro.
     */
    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numero_compte', $numero);
    }

    /**
     * Scope pour récupérer les comptes d'un client basé sur le téléphone.
     */
    public function scopeClient(Builder $query, string $telephone): Builder
    {
        return $query->whereHas('client.user', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    /**
     * Calculer le solde du compte : somme des dépôts et transferts reçus - somme des retraits et transferts envoyés.
     */
    public function calculerSolde(): float
    {
        $credits = $this->transactions()
            ->where(function ($query) {
                $query->where('type', 'depot')
                      ->orWhere(function ($q) {
                          $q->where('type', 'transfert')->whereNull('destinataire_id');
                      });
            })
            ->sum('montant');

        $debits = $this->transactions()
            ->where(function ($query) {
                $query->where('type', 'retrait')
                      ->orWhere(function ($q) {
                          $q->where('type', 'transfert')->whereNotNull('destinataire_id');
                      });
            })
            ->sum('montant');

        return $credits - $debits;
    }

    /**
     * Accessor for solde
     */
    public function getSoldeAttribute(): float
    {
        return $this->calculerSolde();
    }

    /**
     * Generate a unique code_marchand for marchand accounts
     */
    public static function generateCodeMarchand(): string
    {
        do {
            $code = 'MCH' . strtoupper(Str::random(6));
        } while (self::where('code_marchand', $code)->exists());

        return $code;
    }
}
