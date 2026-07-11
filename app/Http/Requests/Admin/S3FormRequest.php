<?php

namespace Pterodactyl\Http\Requests\Admin;

class S3FormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:s3,name,' . $this->route('s3')->id,
            'description' => 'nullable|string|max:1000',
            'access_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'endpoint' => 'nullable|url|max:255',
            'region' => 'nullable|string|max:64',
            'bucket_name' => 'required|string|max:255',
            'use_path_style_endpoint' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        $validated['region'] = trim((string) ($validated['region'] ?? '')) ?: 'us-east-1';
        $validated['use_path_style_endpoint'] = (bool) ($validated['use_path_style_endpoint'] ?? false);
        $validated['enabled'] = (bool) ($validated['enabled'] ?? true);

        return $validated;
    }
}
