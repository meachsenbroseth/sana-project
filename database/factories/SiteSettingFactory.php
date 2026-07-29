<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'banner_image' => null,
            'banner_images' => null,
            'updated_at' => now(),
        ];
    }
}
