<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Banner Management';

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Banner Information')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Banner Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    // Upload untuk Desktop
                    Forms\Components\FileUpload::make('desktop_images')
                        ->label('Desktop Banner Images (1920x600)')
                        ->directory('banners/desktop')
                        ->image()
                        ->imageResizeMode('force')
                        ->imageResizeTargetWidth('1920')
                        ->imageResizeTargetHeight('600')
                        ->multiple()
                        ->minFiles(1)
                        ->maxFiles(10)
                        ->required()
                        ->columnSpan(1)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('Upload banner untuk desktop. Akan diresize ke 1920x600px.'),

                    // Upload untuk Mobile
                    Forms\Components\FileUpload::make('mobile_images')
                        ->label('Mobile Banner Images (4226x3075)')
                        ->directory('banners/mobile')
                        ->image()
                        ->imageResizeMode('force')
                        ->imageResizeTargetWidth('4226')
                        ->imageResizeTargetHeight('3075')
                        ->multiple()
                        ->minFiles(1)
                        ->maxFiles(10)
                        ->required()
                        ->columnSpan(1)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('Upload banner untuk mobile. Akan diresize ke 4226x3075px.'),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first')
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(2)
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('image_count')
                ->label('Images')
                ->getStateUsing(fn ($record) => count($record->image_paths ?? []) . ' images')
                ->badge()
                ->color('primary'),

            Tables\Columns\TextColumn::make('description')
                ->limit(50)
                ->wrap(),

            Tables\Columns\TextColumn::make('sort_order')
                ->label('Order')
                ->sortable()
                ->alignCenter(),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable(),
        ])
        ->defaultSort('sort_order', 'asc')
        ->reorderable('sort_order');
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}