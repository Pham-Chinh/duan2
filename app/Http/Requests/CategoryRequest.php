<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Category;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category') ?? $this->input('category_id');
        
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => [
                'required',
                'string',
                Rule::unique('categories', 'slug')->ignore($categoryId)
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value) {
                        // Kiểm tra không thể chọn chính nó làm cha
                        if ($categoryId && $value == $categoryId) {
                            $fail('Không thể chọn chính nó làm danh mục cha.');
                            return;
                        }
                        
                        // Kiểm tra chỉ cho phép 2 cấp (cha-con)
                        $parent = Category::find($value);
                        if ($parent && $parent->parent_id !== null) {
                            $fail('Chỉ được tạo danh mục con (2 cấp). Không thể tạo cháu (3 cấp).');
                            return;
                        }
                    }
                }
            ],
            'is_visible' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.min' => 'Tên danh mục phải có ít nhất 3 ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique' => 'Tên danh mục này đã tồn tại (slug trùng lặp).',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
        ];
    }
}

