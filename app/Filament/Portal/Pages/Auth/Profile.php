<?php

namespace App\Filament\Portal\Pages\Auth;

use App\Enums\Gender;
use App\Enums\Marrital;
use App\Enums\MemberType;
use App\Enums\Religion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Database\Eloquent\Model;

use function PHPUnit\Framework\isNull;

class Profile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->inlineLabel(false)
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Profile photo')
                        ->schema([
                            Forms\Components\FileUpload::make('avatar')
                                ->image()
                                ->maxSize(1024)
                                ->label(false)
                                ->columnSpanFull()
                                ->inlineLabel(false),
                        ]),
                    Forms\Components\Section::make(__('Membership'))
                        ->relationship('member')
                        ->schema([
                            Forms\Components\ToggleButtons::make('type')
                                ->options(MemberType::class)
                                ->required()
                                ->default(MemberType::Regular)
                                ->inline()
                                ->inlineLabel(false)
                                ->disabled(fn(Forms\Get $get) => $get('joined_at') !== null),
                            Forms\Components\Group::make([
                                Forms\Components\DateTimePicker::make('registered_at')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('interview_at')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('joined_at')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('change_type_at')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('leave_at')
                                    ->seconds(false),
                            ])->disabled()
                        ]),
                ])->columnSpan(1),
                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('Credentials'))
                        ->hiddenOn('create')
                        ->schema([
                            $this->getNameFormComponent(),
                            Forms\Components\TextInput::make('nik')
                                ->label('NIK')
                                ->unique(ignoreRecord: true)
                                ->numeric()
                                ->rules(['digits:16'])
                                ->required()
                                ->live(onBlur: true)
                                ->hint(fn($state) => __('Currently') . ' ' . strlen($state) . ' digits.'),
                            $this->getEmailFormComponent()
                                ->disabled()
                                ->suffixIcon('heroicon-s-lock-closed')
                                ->hint(__('Diverifikasi pada :date', ['date' => $this->getUser()->email_verified_at->format('d M Y')])),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(15),
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                        ])->columns(2),
                    Forms\Components\Section::make('Details')
                        ->relationship('profile')
                        ->schema([
                            Forms\Components\TextInput::make('nickname')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\ToggleButtons::make('gender')
                                ->required()
                                ->inline()
                                ->options(Gender::class),
                            Forms\Components\TextInput::make('pob')
                                ->required()
                                ->label('Place of birth'),
                            Forms\Components\DateTimePicker::make('dob')
                                ->required()
                                ->time(false)
                                ->label('Date of birth'),
                            Forms\Components\Select::make('marrital')
                                ->required()
                                ->options(Marrital::class),
                            Forms\Components\Select::make('religion')
                                ->required()
                                ->options(Religion::class),
                            Forms\Components\TextInput::make('instance')
                                ->required()
                                ->default('UGM')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('faculty')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('major')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('nim')
                                ->label('NIM')
                                ->required()
                                ->numeric()
                                ->maxLength(30),
                            Forms\Components\TextInput::make('work')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('last_education')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\Textarea::make('address')
                                ->required()
                        ])->columns(2),
                    Forms\Components\Section::make(__('Activity'))
                        ->relationship('profile')
                        ->hiddenOn('create')
                        ->schema([
                            Forms\Components\KeyValue::make('meta.activity')
                                ->label(false)
                                ->keyLabel(__('Activity'))
                                ->valueLabel('Position')
                                ->addable(),
                        ])
                ])->columnSpan(2),
            ]);
    }
}
