<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;
use Filament\Tables\Columns\TextColumn;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                  Select::make('user_id')
                  ->label('Customer')
                  ->relationship('user','name')
                  ->required()
                  ->preload()
                  ->searchable(),
                  Section::make()
                  ->schema([
                  Repeater::make('order_items')
                  ->schema([
                    Select::make('product_id')
                    ->label('Product')
                    ->options(fn()=>Product::pluck('title','id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function(Get $get,Set $set){
                        $product=Product::findOrfail($get('product_id'));
                        $set('price',$product->price);
                        $set('sub_total',$get('price')*$get('quantity'));
                    }),
                    TextInput::make('quantity')
                    ->required()
                    ->default(0)
                    ->maxLength(255)
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function(Get $get, Set $set){
                        $set('sub_total',$get('price')*$get('quantity'));
                    }),
                    TextInput::make('price')
                    ->required()
                    ->readOnly(),
                    TextInput::make('sub_total')
                    ->required()
                    ->readOnly()
                  ])->columns(3)
                    ->addAction(function(Get $get,Set $set){
                    $total = collect($get('order_items'))->values()->pluck('sub_total')->sum();
                    $set('totals', $total);
                  }),
                ]),
                  TextInput::make('totals')
                  ->readOnly()
                  ->required()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name'),
                TextColumn::make('totals')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function calculateTotals(Get $get,Set $set):void
    {

    }
}
