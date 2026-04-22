<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use UnitEnum;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Photo;

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected ?SiteSetting $setting = null;

    protected string $view = 'filament.pages.site-settings';

    public function mount(): void
    {
        $this->setting = SiteSetting::query()->firstOrCreate([], [
            'banner_image' => null,
            'banner_images' => null,
        ]);

        $this->form->fill([
            'banners' => $this->setting->normalizedBanners(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site_settings.homepage_banner'))
                    ->description(__('site_settings.homepage_banner_description'))
                    ->schema([
                        Repeater::make('banners')
                            ->label(__('site_settings.banners'))
                            ->addActionLabel(__('site_settings.add_banner'))
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('site_settings.image'))
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('banners')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(4096)
                                    ->downloadable()
                                    ->openable()
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('title')
                                    ->maxLength(255)
                                    ->placeholder(__('site_settings.optional_title'))
                                    ->columnSpan(6),
                                TextInput::make('link')
                                    ->url()
                                    ->maxLength(2048)
                                    ->placeholder(__('site_settings.optional_link'))
                                    ->columnSpan(6),
                                ToggleButtons::make('status')
                                    ->required()
                                    ->inline()
                                    ->default('active')
                                    ->options([
                                        'active' => __('shipping_method.status.active'),
                                        'inactive' => __('shipping_method.status.inactive'),
                                    ])
                                    ->columnSpan(6),
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->columnSpan(6),
                            ])
                            ->columns(12)
                            ->reorderable()
                            ->itemLabel(function (array $state): string {
                                $title = trim((string) ($state['title'] ?? ''));

                                if ($title !== '') {
                                    return $title;
                                }

                                return __('site_settings.banner');
                            })
                            ->helperText(__('site_settings.banners_helper')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! $this->setting) {
            $this->setting = SiteSetting::query()->firstOrCreate([], [
                'banner_image' => null,
                'banner_images' => null,
            ]);
        }

        $oldBannerPaths = collect($this->setting->normalizedBanners())
            ->pluck('image');

        if (filled($this->setting->banner_image)) {
            $oldBannerPaths->push($this->setting->banner_image);
        }

        $newBanners = collect($data['banners'] ?? [])
            ->map(fn (mixed $banner, int $index): ?array => $this->normalizeBannerInput($banner, $index + 1))
            ->filter(fn (?array $banner): bool => is_array($banner))
            ->sortBy('sort_order')
            ->values()
            ->all();

        $newBannerImage = $newBanners[0]['image'] ?? null;
        $newBannerPaths = collect($newBanners)
            ->pluck('image')
            ->unique()
            ->values()
            ->all();

        $this->setting->update([
            'banner_image' => $newBannerImage,
            'banner_images' => $newBanners === [] ? null : $newBanners,
        ]);

        $pathsToDelete = $oldBannerPaths
            ->filter(fn (mixed $path): bool => filled($path))
            ->unique()
            ->reject(fn (string $path): bool => in_array($path, $newBannerPaths, true));

        foreach ($pathsToDelete as $pathToDelete) {
            if (Storage::disk('public')->exists($pathToDelete)) {
                Storage::disk('public')->delete($pathToDelete);
            }
        }

        Notification::make()
            ->title(__('site_settings.saved'))
            ->success()
            ->send();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('site_settings.title');
    }

    public function getShippingMethodsUrl(): string
    {
        return ShippingMethodResource::getUrl();
    }

    /**
     * @param  array<string, mixed>|mixed  $banner
     * @return array{image: string, title: ?string, link: ?string, status: string, sort_order: int}|null
     */
    private function normalizeBannerInput(mixed $banner, int $defaultSortOrder): ?array
    {
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
        $sortOrder = (int) ($banner['sort_order'] ?? $defaultSortOrder);

        if ($sortOrder < 1) {
            $sortOrder = $defaultSortOrder;
        }

        return [
            'image' => $image,
            'title' => $title !== '' ? $title : null,
            'link' => $link !== '' ? $link : null,
            'status' => $status,
            'sort_order' => $sortOrder,
        ];
    }
}
