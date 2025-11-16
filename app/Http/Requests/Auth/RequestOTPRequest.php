<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidTelephoneSenegal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour la demande d'OTP
 */
class RequestOTPRequest extends FormRequest
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
            'telephone' => ['required', 'string', 'exists:users,telephone', new ValidTelephoneSenegal()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.exists' => 'Ce numéro de téléphone n\'est pas enregistré.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'telephone' => 'numéro de téléphone',
        ];
    }
}