<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_COMMENTER = 'commenter';

    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_EDITOR => 'Editor',
        self::ROLE_COMMENTER => 'Commenter',
        self::ROLE_VIEWER => 'Viewer',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'access_permissions',
        'access_modules',
        'record_scope',
        'is_active',
        'job_title',
        'phone',
        'alternative_phone',
        'timezone',
        'avatar_path',
        'middle_name',
        'preferred_name',
        'department',
        'preferred_language',
        'last_active_at',
        'last_login_at',
        'company_id',
        'must_change_password',
        'temporary_password_expires_at',
        'temporary_password_attempts',
        'temporary_password_locked_at',
        'temporary_password_used_at',
        'session_version',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
            'last_login_at' => 'datetime',
            'temporary_password_expires_at' => 'datetime',
            'temporary_password_locked_at' => 'datetime',
            'temporary_password_used_at' => 'datetime',
            'must_change_password' => 'boolean',
            'access_permissions' => 'array',
            'access_modules' => 'array',
            'session_version' => 'integer',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function effectivePermissions(): array
    {
        $role = $this->role ?: self::ROLE_VIEWER;
        $permissions = config('access.role_permissions.'.$role, []);
        if ($permissions === '*') {
            return array_keys(config('access.permissions', []));
        }

        return array_values(array_unique(array_merge($permissions, $this->access_permissions ?? [])));
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function canAccessModule(string $module): bool
    {
        $definition = config('access.modules.'.$module);
        if (! $definition) {
            return false;
        }

        if ($this->access_modules !== null) {
            return in_array($module, $this->access_modules, true) && $this->hasPermission($definition['permission']);
        }

        return $this->hasPermission($definition['permission']);
    }

    public function assignedModuleLabels(): array
    {
        return collect(config('access.modules', []))
            ->filter(fn (array $definition, string $module) => $this->canAccessModule($module))
            ->pluck('label')->values()->all();
    }

    public function isTemporaryPasswordExpired(): bool
    {
        return $this->must_change_password && (! $this->temporary_password_expires_at || $this->temporary_password_expires_at->isPast());
    }

    public function canViewPlanner(): bool
    {
        return $this->hasPermission('planner.view');
    }

    public function canEditPlanner(): bool
    {
        return $this->hasPermission('planner.edit');
    }

    public function canCommentPlanner(): bool
    {
        return $this->hasPermission('planner.comment');
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('users.manage');
    }

    public function canExport(): bool
    {
        return $this->hasPermission('reports.view');
    }

    public function canImport(): bool
    {
        return $this->hasPermission('planner.import');
    }
}
