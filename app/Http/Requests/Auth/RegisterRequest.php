<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidNciSenegal;
use App\Rules\ValidTelephoneSenegal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour l'inscription d'un nouvel utilisateur
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => ['required', 'string', 'unique:users,telephone', new ValidTelephoneSenegal()],
            'password' => 'required|string|min:8|confirmed',
            'cni' => ['required', 'string', 'unique:users,cni', new ValidNciSenegal()],
            'sexe' => 'required|in:Homme,Femme',
            'date_naissance' => 'required|date|before:today',
            'type_compte' => 'nullable|in:marchand,simple',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'cni.required' => 'Le numéro CNI est obligatoire.',
            'cni.unique' => 'Ce numéro CNI est déjà utilisé.',
            'sexe.required' => 'Le sexe est obligatoire.',
            'sexe.in' => 'Le sexe doit être Homme ou Femme.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'type_compte.in' => 'Le type de compte doit être marchand ou simple.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'prenom' => 'prénom',
            'telephone' => 'numéro de téléphone',
            'password' => 'mot de passe',
            'cni' => 'numéro CNI',
            'sexe' => 'sexe',
            'date_naissance' => 'date de naissance',
            'type_compte' => 'type de compte',
        ];
    }
}