<?php

namespace App\Http\Requests\Backend;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            
            // Variant validation
            'variants' => 'nullable|array',
            'variants.*.unit' => 'required|string|max:50',
            'variants.*.quantity_per_unit' => 'nullable|string|max:100',
            'variants.*.price' => 'required|string',
            'variants.*.sale' => 'nullable|integer|min:0|max:100',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.status' => 'nullable|integer|in:1,-1',
        ];
    }

    /**
     * Custom validation: check slug exists (slug generate từ name)
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->name) {
                return;
            }

            $baseSlug = Str::slug(str_replace(['–', '—'], '-', $this->name));
            if ($baseSlug === '') {
                $baseSlug = 'product';
            }

            $query = Product::where('slug', $baseSlug);

            // Nếu là edit thì bỏ qua chính nó
            if ($this->route('product')) {
                $query->where('id', '!=', $this->route('product'));
            }

            if ($query->exists()) {
                $validator->errors()->add(
                    'name',
                    'Tên sản phẩm đã tồn tại (slug bị trùng).'
                );
            }
        });
    }

    /**
     * Message cho validation cơ bản
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm',
            'name.max' => 'Tên sản phẩm tối đa :max ký tự',
            'parent_id.exists' => 'Danh mục cha không hợp lệ',
        ];
    }
}
