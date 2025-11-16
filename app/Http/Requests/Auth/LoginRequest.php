<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidTelephoneSenegal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour la connexion avec mot de passe
 */
class LoginRequest extends FormRequest
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
            'telephone' => ['required', 'string', new ValidTelephoneSenegal()],
            'password' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'telephone' => 'numéro de téléphone',
            'password' => 'mot de passe',
        ];
    }
}