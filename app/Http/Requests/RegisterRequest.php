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
            'telephone' => ['required', 'string', 'unique:users,telephone', new \App\Rules\ValidTelephoneSenegal],
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'cni' => ['required', 'string', 'unique:users,cni', new \App\Rules\ValidNciSenegal],
            'sexe' => 'required|in:Homme,Femme',
            'date_naissance' => 'required|date|before:today',
            'type_compte' => 'nullable|in:marchand,simple',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $telephoneRule = collect($this->rules()['telephone'])
            ->first(fn($rule) => $rule instanceof \App\Rules\ValidTelephoneSenegal);

        if ($telephoneRule && $telephoneRule->getNormalizedValue()) {
            $this->merge([
                'telephone' => $telephoneRule->getNormalizedValue(),
            ]);
        }
    }
}
