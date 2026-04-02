<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mediaAsset = $this->route('mediaAsset');

        return $mediaAsset instanceof MediaAsset
            && ($this->user()?->can('update', $mediaAsset) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(MediaKind::class)],
            'file' => ['nullable', File::image()->max(10 * 1024)],
            'url' => ['nullable', 'url', 'max:2000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'width' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'provider' => ['nullable', 'string', 'max:120'],
            'provider_key' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:12'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'metadata' => ['nullable', 'json'],
            'is_primary' => ['required', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateAllowedKind($validator);
                $this->validateSourceRequirements($validator);
            },
        ];
    }

    public function mediaAsset(): MediaAsset
    {
        /** @var MediaAsset */
        return $this->route('mediaAsset');
    }

    private function validateAllowedKind(Validator $validator): void
    {
        $kind = MediaKind::tryFrom((string) $this->input('kind'));

        if ($kind === null) {
            return;
        }

        $mediable = $this->mediaAsset()->mediable ?? $this->mediaAsset()->mediable_type;

        if (! in_array($kind->value, MediaKind::allowedValuesForMediable($mediable), true)) {
            $validator->errors()->add('kind', 'That media type is not supported for this record.');
        }
    }

    private function validateSourceRequirements(Validator $validator): void
    {
        $kind = MediaKind::tryFrom((string) $this->input('kind'));

        if ($kind === null) {
            return;
        }

        $hasFile = $this->hasFile('file');
        $hasUrl = filled((string) $this->input('url'));

        if (in_array($kind, [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette], true)) {
            if ($hasFile) {
                $validator->errors()->add('file', 'Video assets must be managed as remote metadata links.');
            }

            if (! $hasUrl) {
                $validator->errors()->add('url', 'A public video URL is required for trailers and clips.');
            }

            return;
        }

        if ($hasFile || $hasUrl || $this->mediaAsset()->isUploadBacked()) {
            return;
        }

        $validator->errors()->add('url', 'Provide either an uploaded image or a remote media URL.');
    }
}
