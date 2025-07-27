<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->maxLength(100)
                            ->disabled(), // Order number tidak boleh diubah
                        
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->label('User (if registered)'),
                            
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Customer Name'),
                            
                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->label('Customer Email'),
                            
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Customer Phone'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Order Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default('pending')
                            ->selectablePlaceholder(false)
                            ->native(false),
                            
                        Forms\Components\Select::make('payment_status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default('pending')
                            ->selectablePlaceholder(false)
                            ->native(false),
                            
                        Forms\Components\TextInput::make('payment_method')
                            ->maxLength(255)
                            ->label('Payment Method'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->disabled(), // Calculated field
                            
                        Forms\Components\TextInput::make('tax_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->default(0)
                            ->disabled(), // Calculated field
                            
                        Forms\Components\TextInput::make('shipping_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->default(0),
                            
                        Forms\Components\TextInput::make('discount_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->default(0),
                            
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->disabled(), // Calculated field
                            
                        Forms\Components\TextInput::make('currency')
                            ->required()
                            ->maxLength(3)
                            ->default('IDR')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Customer Addresses')
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Placeholder::make('shipping_address_label')
                                    ->label('Shipping Address')
                                    ->content(function ($record) {
                                        if (!$record || !$record->shipping_address) {
                                            return 'No shipping address';
                                        }
                                        
                                        $address = $record->shipping_address;
                                        $formatted = '';
                                        
                                        if (isset($address['first_name']) && isset($address['last_name'])) {
                                            $formatted .= $address['first_name'] . ' ' . $address['last_name'] . "\n";
                                        }
                                        
                                        if (isset($address['address'])) {
                                            $formatted .= $address['address'] . "\n";
                                        }
                                        
                                        if (isset($address['postal_code'])) {
                                            $formatted .= 'Postal Code: ' . $address['postal_code'] . "\n";
                                        }
                                        
                                        if (isset($address['phone'])) {
                                            $formatted .= 'Phone: ' . $address['phone'];
                                        }
                                        
                                        return $formatted ?: 'No address data';
                                    })
                                    ->extraAttributes(['style' => 'white-space: pre-line; background: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;']),
                            ])
                            ->columnSpan(1),
                            
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Placeholder::make('billing_address_label')
                                    ->label('Billing Address')
                                    ->content(function ($record) {
                                        if (!$record || !$record->billing_address) {
                                            return 'No billing address';
                                        }
                                        
                                        $address = $record->billing_address;
                                        $formatted = '';
                                        
                                        if (isset($address['first_name']) && isset($address['last_name'])) {
                                            $formatted .= $address['first_name'] . ' ' . $address['last_name'] . "\n";
                                        }
                                        
                                        if (isset($address['address'])) {
                                            $formatted .= $address['address'] . "\n";
                                        }
                                        
                                        if (isset($address['postal_code'])) {
                                            $formatted .= 'Postal Code: ' . $address['postal_code'] . "\n";
                                        }
                                        
                                        if (isset($address['phone'])) {
                                            $formatted .= 'Phone: ' . $address['phone'];
                                        }
                                        
                                        return $formatted ?: 'No address data';
                                    })
                                    ->extraAttributes(['style' => 'white-space: pre-line; background: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;']),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Shipping & Tracking')
                    ->schema([
                        Forms\Components\TextInput::make('tracking_number')
                            ->maxLength(255)
                            ->label('Tracking Number')
                            ->placeholder('Enter tracking number when shipped'),
                            
                        Forms\Components\DateTimePicker::make('shipped_at')
                            ->label('Shipped At')
                            ->placeholder('Set when order is shipped'),
                            
                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Delivered At')
                            ->placeholder('Set when order is delivered'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Order Notes')
                            ->rows(3)
                            ->placeholder('Any special notes for this order...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->label('Order #')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                    
                Tables\Columns\TextColumn::make('customer_email')
                    ->searchable()
                    ->label('Email')
                    ->limit(30),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => ['shipped', 'delivered'],
                        'danger' => 'cancelled',
                        'secondary' => 'refunded',
                    ])
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => ['failed', 'cancelled'],
                        'secondary' => 'refunded',
                    ])
                    ->label('Payment')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Total')
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable()
                    ->label('Payment Method')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('tracking_number')
                    ->searchable()
                    ->placeholder('—')
                    ->label('Tracking')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->label('Order Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                    
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                    
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'credit_card' => 'Credit Card',
                        'e_wallet' => 'E-Wallet',
                        'cod' => 'Cash on Delivery',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Quick action to mark as paid
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->payment_status === 'pending')
                    ->action(function (Order $record) {
                        $record->update([
                            'payment_status' => 'paid',
                            'status' => $record->status === 'pending' ? 'processing' : $record->status
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark Order as Paid')
                    ->modalDescription('This will mark the payment as paid and update the order status to processing if it\'s currently pending.'),
                    
                // Quick action to mark as shipped
                Tables\Actions\Action::make('mark_shipped')
                    ->label('Mark Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(fn (Order $record) => in_array($record->status, ['processing', 'pending']) && $record->payment_status === 'paid')
                    ->form([
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->required()
                            ->placeholder('Enter tracking number'),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->update([
                            'status' => 'shipped',
                            'tracking_number' => $data['tracking_number'],
                            'shipped_at' => now(),
                        ]);
                    })
                    ->modalHeading('Mark Order as Shipped'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    
                    // Bulk mark as paid
                    Tables\Actions\BulkAction::make('bulk_mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function($record) {
                                if ($record->payment_status === 'pending') {
                                    $record->update([
                                        'payment_status' => 'paid',
                                        'status' => $record->status === 'pending' ? 'processing' : $record->status
                                    ]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto refresh every 30 seconds
    }

    public static function getRelations(): array
    {
        return [
            // OrderItemsRelationManager::class, // Will be added later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? 'warning' : 'primary';
    }
}