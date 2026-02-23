<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $fillable = [
        'banner_image',
        'banner_images',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'banner_images' => 'array',
        ];
    }

    /**
     * @return array<int, array{image: string, title: ?string, link: ?string, status: string, sort_order: int}>
     */
    public function normalizedBanners(): array
    {
        $rawBanners = $this->banner_images ?? [];

        if (! is_array($rawBanners)) {
            $rawBanners = [];
        }

        if ($rawBanners === [] && filled($this->banner_image)) {
            $rawBanners = [
                [
                    'image' => $this->banner_image,
                    'title' => null,
                    'link' => null,
                    'status' => 'active',
                    'sort_order' => 1,
                ],
            ];
        }

        return collect($rawBanners)
            ->map(function (mixed $banner, int $index): ?array {
                if (is_string($banner)) {
                    $image = trim($banner);

                    if ($image === '') {
                        return null;
                    }

                    return [
                        'image' => $image,
                        'title' => null,
                        'link' => null,
                        'status' => 'active',
                        'sort_order' => $index + 1,
                    ];
                }

                if (! is_array($banner)) {
                    return null;
                }

                $image = trim((string) ($banner['image'] ?? ''));

                if ($image === '') {
                    return null;
                }

                $title = trim((string) ($banner['title'] ?? ''));
                $link = trim((string) ($banner['link'] ?? ''));
                $status = ($banner['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
                $sortOrder = (int) ($banner['sort_order'] ?? ($index + 1));

                if ($sortOrder < 1) {
                    $sortOrder = $index + 1;
                }

                return [
                    'image' => $image,
                    'title' => $title !== '' ? $title : null,
                    'link' => $link !== '' ? $link : null,
                    'status' => $status,
                    'sort_order' => $sortOrder,
                ];
            })
            ->filter(fn (?array $banner): bool => is_array($banner))
            ->sortBy('sort_order')
            ->values()
            ->all();
    }
}
