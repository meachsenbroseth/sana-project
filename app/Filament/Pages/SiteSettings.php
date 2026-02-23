<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Photo;

    protected static ?string $navigationLabel = 'Site Settings';

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
        ]);

        $this->form->fill([
            'banner_image' => $this->setting->banner_image,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Homepage Banner')
                    ->description('Upload a single active homepage banner image.')
                    ->schema([
                        FileUpload::make('banner_image')
                            ->label('Banner Image')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('banners')
                            ->image()
                            ->imageEditor()
                            ->maxSize(4096)
                            ->downloadable()
                            ->openable()
                            ->helperText('Recommended: wide image (e.g., 1920x800).'),
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
            ]);
        }

        $oldBannerImage = $this->setting->banner_image;
        $newBannerImage = $data['banner_image'] ?? null;

        $this->setting->update([
            'banner_image' => $newBannerImage,
        ]);

        if (
            filled($oldBannerImage) &&
            $oldBannerImage !== $newBannerImage &&
            Storage::disk('public')->exists($oldBannerImage)
        ) {
            Storage::disk('public')->delete($oldBannerImage);
        }

        Notification::make()
            ->title('Site settings saved')
            ->success()
            ->send();
    }

    public function getShippingMethodsUrl(): string
    {
        return ShippingMethodResource::getUrl();
    }
}
