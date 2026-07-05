<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'            => 'required|exists:products,id',
            'batch_number'          => [
                'required', 'string', 'max:255',
                // No duplicate batch number for the same product (ignoring self & soft-deleted)
                Rule::unique('batches')->where(fn ($q) => $q
                    ->where('product_id', $this->input('product_id'))
                    ->whereNull('deleted_at'))
                    ->ignore($this->route('batch')->id),
            ],
            'lot_number'            => 'nullable|string|max:255',
            'manufacture_date'      => 'required|date',
            'expiry_date'           => 'required|date|after:manufacture_date',
            'quantity_produced'     => 'required|integer|min:0',
            'quantity_available'    => 'nullable|integer|min:0',
            'manufacturing_site'    => 'nullable|string|max:255',
            'manufacturing_country' => 'nullable|string|max:5',
            'qc_status'             => 'nullable|in:pending,released,quarantine,rejected,recalled',
            'qc_approved_by'        => 'nullable|string|max:255',
            'qc_approval_date'      => 'nullable|date',
            'storage_conditions'    => 'nullable|string|max:255',
            'storage_temp_min'      => 'nullable|numeric',
            'storage_temp_max'      => 'nullable|numeric',
            'status'                => 'nullable|in:active,expired,recalled,quarantine,depleted',
            'notes'                 => 'nullable|string|max:2000',
            'coa'                   => 'nullable|file|mimes:pdf|max:10240',
            'remove_coa'            => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'   => 'Please select a product.',
            'batch_number.required' => 'Batch number is required.',
            'batch_number.unique'   => 'This product already has a batch with that batch number.',
            'expiry_date.after'     => 'Expiry date must be after the manufacture date.',
            'coa.mimes'             => 'The COA must be a PDF file.',
            'coa.max'               => 'The COA may not be larger than 10 MB.',
        ];
    }
}
