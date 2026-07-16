<?php

namespace Pterodactyl\Http\Requests\Admin;

class BucketFormRequest extends AdminFormRequest
{
    /**
     * Rules to apply to requests for creating or updating an S3 bucket
     * in the Admin CP.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:s3,name' . ($this->route('bucket') ? ',' . $this->route('bucket')->id : ''),
            'description' => 'nullable|string|max:1000',
            'access_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'endpoint' => 'nullable|url|max:255',
            'region' => 'nullable|url|max:255',
            'bucket_name' => 'required|string|max:255',
            'use_path_style_endpoint' => 'boolean',
            /* 'enabled' => 'boolean', */
        ];
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Ensure boolean fields are properly cast
        $validated['use_path_style_endpoint'] = (bool) ($validated['use_path_style_endpoint'] ?? false);
        $validated['enabled'] = (bool) ($validated['enabled'] ?? true);

        return $validated;
    }
}
