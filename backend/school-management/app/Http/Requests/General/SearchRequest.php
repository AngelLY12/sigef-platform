<?php

namespace App\Http\Requests\General;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
            'search' => 'required|string|min:1|max:50',
            'limit' => 'sometimes|integer|min:1|max:50',
            'forceRefresh' => 'sometimes|boolean'
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'search' => trim($this->search ?? ''),
            'limit' => $this->limit ?? 15
        ]);
    }
}
