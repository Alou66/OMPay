<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'telephone' => ['required', 'string', new \App\Rules\ValidTelephoneSenegal],
            'otp' => 'required|string|size:6',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'cni' => ['required', 'string', new \App\Rules\ValidNciSenegal],
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date|before:today',
        ];
    }
}
