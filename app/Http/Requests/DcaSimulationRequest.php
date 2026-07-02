<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DcaSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'exists:assets,id'],
            'monthly_amount' => ['required', 'numeric', 'gt:0'],
            'start_month' => ['required', 'date_format:Y-m'],
        ];
    }
}
