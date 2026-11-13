<?php

namespace App\Livewire;

use App\Models\Setting;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingGreetingCard extends Component
{
    use WithFileUploads;

    public $enabled, $template, $old_template, $template_width, $template_height;
    public $background, $old_background, $remove_background = false;
    public $title, $description;
    public $name_x, $name_y, $name_max_width, $font_size, $font_family, $font_weight, $name_color;
    public $sample_name = 'আপনার নাম';

    public function mount()
    {
        $saved = Setting::where('key', 'greeting_card')->value('value') ?? [];
        if (is_string($saved)) {
            $saved = json_decode($saved, true) ?: [];
        }
        $data = array_merge(config('greeting_card.defaults'), $saved);

        $this->enabled = (bool) $data['enabled'];
        $this->old_template = $data['template'];
        $this->old_background = $data['background'];
        $this->template_width = (int) $data['template_width'];
        $this->template_height = (int) $data['template_height'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->name_x = $data['name_x'];
        $this->name_y = $data['name_y'];
        $this->name_max_width = $data['name_max_width'];
        $this->font_size = $data['font_size'];
        $this->font_family = $data['font_family'];
        $this->font_weight = (string) $data['font_weight'];
        $this->name_color = $data['name_color'];
    }

    public function updatedTemplate()
    {
        $this->validateUpload('template', $this->templateRule());
        [$this->template_width, $this->template_height] = getimagesize($this->template->getRealPath());
    }

    public function updatedBackground()
    {
        $this->validateUpload('background', $this->backgroundRule());
        $this->remove_background = false;
    }

    // Drop a bad file so the preview never calls temporaryUrl() on it.
    protected function validateUpload(string $field, string $rule): void
    {
        try {
            $this->validateOnly($field, [$field => $rule]);
        } catch (ValidationException $e) {
            $this->reset($field);
            throw $e;
        }
    }

    protected function templateRule(): string
    {
        return 'nullable|image|mimes:png|max:10240|dimensions:min_width=500,min_height=500';
    }

    protected function backgroundRule(): string
    {
        return 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240';
    }

    public function removeBackground()
    {
        $this->reset('background');
        $this->remove_background = true; // file is deleted on Save
    }

    public function render()
    {
        return view('livewire.setting-greeting-card', [
            'fonts' => array_keys(config('greeting_card.fonts')),
        ]);
    }

    /**
     * Copy an upload into public/uploads/greeting-card and delete the file it replaces.
     * Timestamped name so browsers/CDNs don't serve a cached old image.
     */
    protected function storeUpload($file, string $prefix, ?string $old): ?string
    {
        $dir = public_path('uploads/greeting-card');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = "uploads/greeting-card/{$prefix}-" . time() . '.' . $file->extension();
        if (!copy($file->getRealPath(), public_path($path))) {
            return null;
        }
        $this->deleteFile($old);
        return $path;
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }

    public function updateSetting()
    {
        $this->validate([
            'template' => $this->templateRule(),
            'background' => $this->backgroundRule(),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'name_x' => 'required|numeric|min:0|max:100',
            'name_y' => 'required|numeric|min:0|max:100',
            'name_max_width' => 'required|numeric|min:5|max:100',
            'font_size' => 'required|integer|min:10|max:400',
            'font_family' => 'required|in:' . implode(',', array_keys(config('greeting_card.fonts'))),
            'font_weight' => 'required|in:400,700',
            'name_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        if ($this->template) {
            $path = $this->storeUpload($this->template, 'template', $this->old_template);
            if (!$path) {
                $this->addError('template', 'Could not save the template file.');
                return;
            }
            $this->old_template = $path;
            $this->template = null;
        }

        if ($this->background) {
            $path = $this->storeUpload($this->background, 'background', $this->old_background);
            if (!$path) {
                $this->addError('background', 'Could not save the background file.');
                return;
            }
            $this->old_background = $path;
            $this->background = null;
        } elseif ($this->remove_background) {
            $this->deleteFile($this->old_background);
            $this->old_background = null;
        }
        $this->remove_background = false;

        Setting::updateOrCreate(['key' => 'greeting_card'], ['value' => [
            'enabled' => (bool) $this->enabled,
            'template' => $this->old_template,
            'background' => $this->old_background,
            'template_width' => (int) $this->template_width,
            'template_height' => (int) $this->template_height,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'name_x' => round((float) $this->name_x, 2),
            'name_y' => round((float) $this->name_y, 2),
            'name_max_width' => round((float) $this->name_max_width, 2),
            'font_size' => (int) $this->font_size,
            'font_family' => $this->font_family,
            'font_weight' => (string) $this->font_weight,
            'name_color' => $this->name_color,
        ]]);

        cache()->forget('settings');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Greeting card settings updated!']);
    }
}
