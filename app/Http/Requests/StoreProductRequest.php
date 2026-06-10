<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: gate check e.g. auth()->user()->can('create-product')
    }

    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:255',
            'generic_name'            => 'nullable|string|max:255',
            'brand_name'              => 'nullable|string|max:255',
            'dosage_form'             => 'required|in:tablet,capsule,injection,syrup,cream,ointment,drops,inhaler,other',
            'strength'                => 'nullable|string|max:50',
            'pack_size'               => 'nullable|string|max:100',
            'atc_code'                => 'nullable|string|max:20',
            'hs_code'                 => 'nullable|string|max:20',
            'controlled_substance'    => 'nullable|in:no,schedule_1,schedule_2,schedule_3',
            'manufacturer_name'       => 'nullable|string|max:255',
            'manufacturing_site'      => 'nullable|string|max:255',
            'country_of_origin'       => 'nullable|string|max:5',
            'shelf_life'              => 'nullable|string|max:50',
            'storage_conditions'      => 'nullable|string|max:255',
            'temperature_sensitivity' => 'nullable|in:ambient,cool_chain,cold_chain,frozen',
            'unit_cost'               => 'nullable|numeric|min:0',
            'unit_of_measure'         => 'nullable|string|max:20',
            'status'                  => 'nullable|in:active,discontinued,pending_approval',
            'notes'                   => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Product brand name is required.',
            'dosage_form.required' => 'Please select a dosage form.',
            'dosage_form.in'       => 'Selected dosage form is not valid.',
        ];
    }
}
