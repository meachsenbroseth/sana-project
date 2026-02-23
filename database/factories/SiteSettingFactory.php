<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteSetting>
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
