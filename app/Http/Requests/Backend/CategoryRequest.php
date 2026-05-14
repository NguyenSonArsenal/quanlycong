<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use App\Models\Category;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:category,id'],
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
                $baseSlug = 'category';
            }

            $query = Category::where('slug', $baseSlug);

            // Nếu là edit thì bỏ qua chính nó
            if ($this->route('category')) {
                $query->where('id', '!=', $this->route('category'));
            }

            if ($query->exists()) {
                $validator->errors()->add(
                    'name',
                    'Tên danh mục đã tồn tại (slug bị trùng).'
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
            'name.required' => 'Vui lòng nhập tên danh mục',
            'name.max' => 'Tên danh mục tối đa :max ký tự',
            'parent_id.exists' => 'Danh mục cha không hợp lệ',
        ];
    }
}
