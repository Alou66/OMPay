<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
            'recipient_telephone' => ['required', 'string', new \App\Rules\ValidTelephoneSenegal],
            'amount' => 'required|numeric|min:100|max:1000000',
            'description' => 'nullable|string|max:255',
        ];
    }
}
