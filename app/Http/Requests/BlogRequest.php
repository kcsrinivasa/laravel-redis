<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class BlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * This function return the error response as json if any validation fail. 
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
         'success'   => false,
         'message'   => 'Validation errors',
         'data'      => $validator->errors()
       ],422));
    }

    /**
     * This function optional to return custom error message if any validation fail. 
     */
    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'description.required' => 'Description is required, Please add more details for the blog',
        ];
     }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'metaTitle' => 'nullable|string',
            'metaDescription' => 'nullable|string',
        ];
    }
}
