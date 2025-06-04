<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseTransactionRequest extends FormRequest
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
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'O ID da transação é obrigatório.',
            'transaction_id.integer' => 'O ID da transação deve ser um número inteiro.',
            'transaction_id.exists' => 'A transação com o ID fornecido não existe.',
            'reason.string' => 'O motivo do estorno deve ser um texto.',
            'reason.max' => 'O motivo do estorno não pode exceder :max caracteres.',
        ];
    }
}
