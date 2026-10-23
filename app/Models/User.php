<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $guard_name = 'web';

    private array $securityAttributes = [];

    protected function twoFactorSecret(): Attribute
    {
        return $this->publishingSecurityAttribute('two_factor_secret');
    }

    protected function twoFactorRecoveryCodes(): Attribute
    {
        return $this->publishingSecurityAttribute('two_factor_recovery_codes');
    }

    protected function twoFactorConfirmedAt(): Attribute
    {
        return $this->publishingSecurityAttribute('two_factor_confirmed_at');
    }

    private function publishingSecurityAttribute(string $column): Attribute
    {
        return Attribute::make(
            get: fn () => array_key_exists($column, $this->securityAttributes)
                ? $this->securityAttributes[$column]
                : $this->profile?->{$column},
            set: function ($value) use ($column): array {
                $this->securityAttributes[$column] = $value;

                return [];
            },
        );
    }

    public function save(array $options = [])
    {
        if ($this->securityAttributes === []) {
            return parent::save($options);
        }

        // Fortify writes through User; its encrypted factors belong only to publishing.
        return $this->getConnection()->transaction(function () use ($options): bool {
            if (! parent::save($options)) {
                return false;
            }
            $profile = $this->profile()->firstOrNew([], ['display_name' => $this->name]);
            $profile->forceFill($this->securityAttributes)->save();
            $this->setRelation('profile', $profile);
            $this->securityAttributes = [];

            return true;
        });
    }

    public function synchronizePassword(string $password): bool
    {
        if ($this->password && Hash::check($password, $this->password) && ! Hash::needsRehash($this->password)) {
            return false;
        }

        $this->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        return true;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(PoetryCollection::class, 'author_id');
    }

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class, 'author_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'author_id');
    }

    public function generatedAssets(): HasMany
    {
        return $this->hasMany(GeneratedAsset::class, 'author_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }
}
