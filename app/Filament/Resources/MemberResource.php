<?php

namespace App\Filament\Resources;

use App\Enums\Gender;
use App\Enums\Marrital;
use Filament\Forms;
use Filament\Tables;
use App\Models\Member;
use Filament\Forms\Form;
use App\Enums\MemberType;
use Filament\Tables\Table;
use App\Enums\MemberStatus;
use App\Enums\Religion;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use App\Filament\Components as AppComponents;
use App\Filament\Resources\MemberResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\User;
use Filament\Pages\Page;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function getModelLabel(): string
    {
        return __('Member');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Membership');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('Membership'))
                        ->schema([
                            Forms\Components\TextInput::make('code')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('user_id')
                                ->disabledOn('edit')
                                ->relationship('user', 'name', fn(Builder $query) => $query->doesntHave('member'))
                                ->getOptionLabelUsing(fn($value) => User::find($value)?->name ?? $value)
                                ->preload()
                                ->placeholder(__('Select user or create new'))
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\Group::make([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->minLength(3)
                                            ->maxLength(200),
                                        Forms\Components\TextInput::make('nik')
                                            ->label('NIK')
                                            ->unique(ignoreRecord: true)
                                            ->numeric()
                                            ->rules(['digits:16'])
                                            ->required()
                                            ->live(onBlur: true)
                                            ->hint(fn($state) => __('Currently') . ' ' . strlen($state) . ' digits.'),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('password')
                                            ->password()
                                            ->revealable(filament()->arePasswordsRevealable())
                                            ->rule(Password::default())
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn($state): bool => filled($state))
                                            ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                                            ->live(debounce: 500)
                                            ->same('passwordConfirmation'),
                                        Forms\Components\TextInput::make('passwordConfirmation')
                                            ->password()
                                            ->revealable(filament()->arePasswordsRevealable())
                                            ->required()
                                            ->visible(fn(Forms\Get $get): bool => filled($get('password')))
                                            ->dehydrated(false),
                                    ])->columns(2),
                                ]),
                            Forms\Components\Select::make('type')
                                ->options(MemberType::class)
                                ->required()
                                ->default(MemberType::Regular)
                                ->preload(),
                            Forms\Components\DateTimePicker::make('joined_at')
                                ->default(now())
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('change_type_at')
                                ->seconds(false)
                                ->hiddenOn('create'),
                            Forms\Components\DateTimePicker::make('leave_at')
                                ->seconds(false)
                                ->hiddenOn('create'),
                        ])->columns(2),
                    Forms\Components\Section::make(__('Credentials'))
                        ->relationship('user')
                        ->hiddenOn('create')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->minLength(3)
                                ->maxLength(200)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('nik')
                                ->label('NIK')
                                ->unique(ignoreRecord: true)
                                ->numeric()
                                ->rules(['digits:16'])
                                ->required()
                                ->live(onBlur: true)
                                ->hint(fn($state) => __('Currently') . ' ' . strlen($state) . ' digits.'),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(15),
                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->revealable(filament()->arePasswordsRevealable())
                                ->rule(Password::default())
                                ->autocomplete('new-password')
                                ->dehydrated(fn($state): bool => filled($state))
                                ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                                ->live(debounce: 500)
                                ->same('passwordConfirmation'),
                            Forms\Components\TextInput::make('passwordConfirmation')
                                ->password()
                                ->revealable(filament()->arePasswordsRevealable())
                                ->required()
                                ->visible(fn(Forms\Get $get): bool => filled($get('password')))
                                ->dehydrated(false),
                        ])->columns(2),
                    Forms\Components\Section::make('Details')
                        ->relationship('profile')
                        ->hiddenOn('create')
                        ->schema([
                            Forms\Components\TextInput::make('nickname')
                                ->maxLength(100),
                            Forms\Components\ToggleButtons::make('gender')
                                ->inline()
                                ->options(Gender::class),
                            Forms\Components\TextInput::make('pob')
                                ->label('Place of birth'),
                            Forms\Components\DatePicker::make('dob')
                                ->label('Date of birth'),
                            Forms\Components\Select::make('marrital')
                                ->options(Marrital::class),
                            Forms\Components\Select::make('religion')
                                ->options(Religion::class),
                            Forms\Components\TextInput::make('instance')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('faculty')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('major')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('nim')
                                ->label('NIM')
                                ->numeric()
                                ->maxLength(30),
                            Forms\Components\TextInput::make('work')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('last_education')
                                ->maxLength(100),
                            Forms\Components\Textarea::make('address'),
                        ])->columns(2),
                    Forms\Components\Section::make(__('Activity'))
                        ->relationship('profile')
                        ->hiddenOn('create')
                        ->schema([
                            Forms\Components\KeyValue::make('meta.activity')
                                ->label(false)
                                ->keyLabel(__('Activity'))
                                ->valueLabel(__('Position'))
                                ->addable(),
                        ])
                ])->columnSpan(2),
                Forms\Components\Group::make([
                    Forms\Components\Section::make([
                        Forms\Components\ToggleButtons::make('status')
                            ->boolean(__('Active'), __('Inactive'))
                            ->inline()
                            ->required(),
                    ]),
                    Forms\Components\Section::make([
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->maxSize(1024)
                            ->label('Profile photo'),
                    ])->relationship('user')
                        ->hiddenOn('create'),
                    AppComponents\Forms\TimestampPlaceholder::make()
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                AppComponents\Columns\IDColumn::make(),
                Tables\Columns\TextColumn::make('code')
                    ->label('NAK')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->translateLabel()
                    ->sortable(),
                AppComponents\Columns\WhatsappLinkColumn::make('user.phone')
                    ->label('Whatsapp')
                    ->translateLabel()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\ToggleColumn::make('status')
                    ->onIcon('heroicon-s-check-circle')
                    ->offIcon('heroicon-s-x-circle')
                    ->onColor('primary')
                    ->offColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.wallet.balance')
                    ->label('Balance')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('joined_at')
                    ->dateTime('d F Y')
                    ->sortable(),
                AppComponents\Columns\LastModifiedColumn::make(),
                AppComponents\Columns\CreatedAtColumn::make(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(MemberType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->options([true => __('Active'), false => __('Inactive')]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'saving-cycles' => Pages\ManageSavingCycleMembers::route('/{record}/saving-cycles'),
            'transactions' => Pages\ManageTransactions::route('/{record}/transactions'),
            'documents' => Pages\ManageDocuments::route('/{record}/documents'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditMember::class,
            Pages\ManageSavingCycleMembers::class,
            Pages\ManageTransactions::class,
            Pages\ManageDocuments::class,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->member()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
