<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SafariBoardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TemporaryPasswordController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public home redirects to login or planner depending on auth state
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('planner');
    }

    return redirect()->route('login');
})->name('home');

// Auth routes - Guest middleware
Route::middleware('guest')->group(function () {
    // Keep /auth/login as the single, canonical entry point for authentication.
    Route::get('/auth/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login.store')->middleware('throttle:5,1');

    // Legacy aliases keep old bookmarks and deployed links working while sending
    // visitors to the canonical auth URL.
    Route::redirect('/login', '/auth/login')->name('login.legacy');
    Route::post('/login', [AuthController::class, 'login'])->name('login.legacy.store')->middleware('throttle:5,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:3,1');
});

Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invite/{token}', [InvitationController::class, 'accept'])->name('invitation.accept')->middleware('throttle:5,1');
Route::middleware(['auth', 'active', 'session.valid'])->group(function () {
    Route::get('/password/temporary', [TemporaryPasswordController::class, 'edit'])->name('temporary-password.edit');
    Route::post('/password/temporary', [TemporaryPasswordController::class, 'update'])->name('temporary-password.update')->middleware('throttle:5,1');
});

// Protected routes
Route::middleware(['auth', 'active', 'session.valid', 'password.changed'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Planner routes - Using controller
    Route::get('/dashboard', [PlannerController::class, 'index'])->name('dashboard')->middleware(['permission:planner.view', 'module:planner-overview']);
    // Keep the historical route name pointed at the canonical dashboard URL.
    Route::get('/dashboard/{legacy?}', [PlannerController::class, 'index'])->name('planner')->middleware(['permission:planner.view', 'module:planner-overview']);
    Route::get('/planner', [PlannerController::class, 'index'])->name('planner.legacy')->middleware(['permission:planner.view', 'module:planner-overview']);
    Route::get('/planner/overview', [PlannerController::class, 'overviewData'])->name('planner.overview')->middleware(['permission:planner.view', 'module:planner-overview']);
    Route::get('/planner/bookings', [SafariBoardController::class, 'defaultIndex'])->name('planner.bookings')->middleware(['permission:planner.view', 'module:planner-bookings']);
    Route::post('/safari-board/{year}/{month}/import', [SafariBoardController::class, 'import'])->whereNumber('year')->name('safari-board.import')->middleware('permission:planner.import');
    Route::get('/safari-board/{year}/{month}/import-template', [SafariBoardController::class, 'importTemplate'])->whereNumber('year')->name('safari-board.import-template')->middleware('permission:planner.import');
    Route::get('/safari-board/{year}/{month}', [SafariBoardController::class, 'index'])->whereNumber('year')->name('safari-board.index')->middleware(['permission:planner.view', 'module:planner-bookings']);
    Route::post('/safari-board/{year}/{month}/rows', [SafariBoardController::class, 'store'])->whereNumber('year')->name('safari-board.store')->middleware('permission:planner.edit');
    Route::post('/safari-board/{year}/{month}/copy', [SafariBoardController::class, 'copyMonth'])->whereNumber('year')->name('safari-board.copy-month')->middleware('permission:planner.edit');
    Route::post('/safari-board/{year}/archive', [SafariBoardController::class, 'archiveYear'])->whereNumber('year')->name('safari-board.archive-year')->middleware('permission:planner.edit');
    Route::patch('/safari-board/records/{booking}', [SafariBoardController::class, 'update'])->name('safari-board.update')->middleware('permission:planner.edit');
    Route::patch('/safari-board/records/{booking}/inline', [SafariBoardController::class, 'inlineUpdate'])->name('safari-board.inline-update')->middleware('permission:planner.edit');
    Route::post('/safari-board/records/{booking}/duplicate', [SafariBoardController::class, 'duplicate'])->name('safari-board.duplicate')->middleware('permission:planner.edit');
    Route::delete('/safari-board/records/{booking}', [SafariBoardController::class, 'destroy'])->name('safari-board.destroy')->middleware('permission:planner.edit');
    Route::post('/safari-board/filter-views', [SafariBoardController::class, 'saveView'])->name('safari-board.views.store');
    Route::delete('/safari-board/filter-views/{filterView}', [SafariBoardController::class, 'deleteView'])->name('safari-board.views.destroy');
    Route::get('/planner/calendar', [PlannerController::class, 'index'])->name('planner.calendar')->middleware(['permission:planner.view', 'module:planner-calendar']);
    Route::get('/planner/tasks', [TaskController::class, 'index'])->name('planner.tasks')->middleware(['permission:tasks.view', 'module:planner-tasks']);
    Route::post('/planner/tasks', [TaskController::class, 'store'])->name('planner.tasks.store')->middleware('permission:tasks.manage');
    Route::put('/planner/tasks/{task}', [TaskController::class, 'update'])->name('planner.tasks.update')->middleware('permission:tasks.manage');
    Route::delete('/planner/tasks/{task}', [TaskController::class, 'destroy'])->name('planner.tasks.destroy')->middleware('permission:tasks.manage');
    Route::get('/planner/transfers/export/{year}/{month}', [PlannerController::class, 'exportTransfers'])->whereNumber('year')->name('planner.transfers.export')->middleware('permission:reports.view');
    Route::post('/planner/transfers/{year}/{month}/import', [PlannerController::class, 'importTransfers'])->whereNumber('year')->name('planner.transfers.import')->middleware('permission:transfers.edit');
    Route::get('/planner/transfers/{year}/{month}/import-template', [PlannerController::class, 'transferImportTemplate'])->whereNumber('year')->name('planner.transfers.import-template')->middleware('permission:transfers.edit');
    Route::get('/planner/transfers/{year?}/{month?}', [PlannerController::class, 'index'])->whereNumber('year')->name('planner.transfers')->middleware(['permission:transfers.view', 'module:planner-transfers']);
    Route::get('/planner/guides', [PlannerController::class, 'index'])->name('planner.guides')->middleware(['permission:guides.view', 'module:planner-guides']);
    Route::get('/planner/guides/import-template', [PlannerController::class, 'resourceImportTemplate'])->name('planner.guides.template')->middleware('permission:guides.edit');
    Route::put('/planner/guides/{guide}', [PlannerController::class, 'updateGuide'])->name('planner.guides.update')->middleware('permission:guides.edit');
    Route::delete('/planner/guides/{guide}', [PlannerController::class, 'destroyGuide'])->name('planner.guides.destroy')->middleware('permission:guides.edit');
    Route::get('/planner/guides/{guide}', [PlannerController::class, 'guide'])->name('planner.guides.show')->middleware(['permission:guides.view', 'module:planner-guides']);
    Route::get('/planner/guides/export/{format}', [PlannerController::class, 'exportResource'])->name('planner.guides.export')->middleware('permission:reports.view');
    Route::post('/planner/guides/import', [PlannerController::class, 'importResource'])->name('planner.guides.import')->middleware('permission:guides.edit');
    Route::post('/planner/guides', [PlannerController::class, 'storeGuide'])->name('planner.guides.store')->middleware('permission:guides.edit');
    Route::get('/planner/vehicles', [PlannerController::class, 'index'])->name('planner.vehicles')->middleware(['permission:vehicles.view', 'module:planner-vehicles']);
    Route::get('/planner/vehicles/import-template', [PlannerController::class, 'resourceImportTemplate'])->name('planner.vehicles.template')->middleware('permission:vehicles.edit');
    Route::put('/planner/vehicles/{vehicle}', [PlannerController::class, 'updateVehicle'])->name('planner.vehicles.update')->middleware('permission:vehicles.edit');
    Route::delete('/planner/vehicles/{vehicle}', [PlannerController::class, 'destroyVehicle'])->name('planner.vehicles.destroy')->middleware('permission:vehicles.edit');
    Route::get('/planner/vehicles/{vehicle}', [PlannerController::class, 'vehicle'])->name('planner.vehicles.show')->middleware(['permission:vehicles.view', 'module:planner-vehicles']);
    Route::get('/planner/vehicles/export/{format}', [PlannerController::class, 'exportResource'])->name('planner.vehicles.export')->middleware('permission:reports.view');
    Route::post('/planner/vehicles/import', [PlannerController::class, 'importResource'])->name('planner.vehicles.import')->middleware('permission:vehicles.edit');
    Route::post('/planner/vehicles', [PlannerController::class, 'storeVehicle'])->name('planner.vehicles.store')->middleware('permission:vehicles.edit');
    Route::post('/planner/transfers', [PlannerController::class, 'storeTransfer'])->name('planner.transfers.store')->middleware('permission:transfers.edit');
    Route::patch('/planner/transfers/{transfer}', [PlannerController::class, 'updateTransfer'])->name('planner.transfers.update')->middleware('permission:transfers.edit');
    Route::post('/planner/transfers/{transfer}/duplicate', [PlannerController::class, 'duplicateTransfer'])->name('planner.transfers.duplicate')->middleware('permission:transfers.edit');
    Route::post('/planner/transfers/{transfer}/cancel', [PlannerController::class, 'cancelTransfer'])->name('planner.transfers.cancel')->middleware('permission:transfers.edit');
    Route::post('/planner/transfers/{transfer}/archive', [PlannerController::class, 'archiveTransfer'])->name('planner.transfers.archive')->middleware('permission:transfers.edit');
    Route::redirect('/planner/resources', '/planner/guides')->name('planner.resources');
    Route::get('/planner/reports', [PlannerController::class, 'index'])->name('planner.reports')->middleware(['permission:reports.view', 'module:planner-reports']);
    Route::get('/planner/documents', [PlannerController::class, 'index'])->name('planner.documents')->middleware(['permission:documents.view', 'module:planner-documents']);
    Route::post('/planner/documents', [PlannerController::class, 'uploadDocument'])->name('planner.documents.upload')->middleware('permission:documents.edit');
    Route::get('/planner/team', fn () => redirect()->route('settings.team'))->name('planner.team')->middleware(['permission:users.view', 'module:planner-team']);
    Route::post('/planner/team/invite', [PlannerController::class, 'inviteTeammate'])->name('planner.team.invite')->middleware('permission:users.manage');
    Route::get('/planner/settings', [PlannerController::class, 'index'])->name('planner.settings');
    Route::get('/planner/profile', [PlannerController::class, 'index'])->name('planner.profile');

    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/profile', [SettingsController::class, 'profileIndex'])->name('profile');
        Route::get('/personal', [SettingsController::class, 'personalIndex'])->name('personal');
        Route::post('/personal', [SettingsController::class, 'personalUpdate'])->name('personal.update');
        Route::get('/security', [SettingsController::class, 'securityIndex'])->name('security');
        Route::post('/security/password', [SettingsController::class, 'updatePassword'])->name('security.password');
        Route::get('/notifications', [SettingsController::class, 'notificationsIndex'])->name('notifications');
        Route::post('/notifications', [SettingsController::class, 'notificationsUpdate'])->name('notifications.update');
        Route::get('/preferences', [SettingsController::class, 'preferencesIndex'])->name('preferences');
        Route::post('/preferences', [SettingsController::class, 'preferencesUpdate'])->name('preferences.update');
        Route::get('/sessions', [SettingsController::class, 'sessionsIndex'])->name('sessions');
    });

    // Workspace settings (admin only)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/workspace', [SettingsController::class, 'workspaceIndex'])->name('workspace')->middleware('permission:workspace.view');
        Route::post('/workspace', [SettingsController::class, 'workspaceUpdate'])->name('workspace.update')->middleware('permission:workspace.manage');
        Route::get('/business', [SettingsController::class, 'businessIndex'])->name('business')->middleware('permission:workspace.view');
        Route::get('/team', [SettingsController::class, 'teamIndex'])->name('team')->middleware(['permission:users.view', 'module:planner-team']);
        Route::get('/roles', [SettingsController::class, 'rolesIndex'])->name('roles')->middleware('permission:roles.manage');
        Route::get('/transfers', [SettingsController::class, 'transfersIndex'])->name('transfers')->middleware('permission:workspace.manage');
        Route::get('/notification-templates', [SettingsController::class, 'notificationTemplatesIndex'])->name('notification-templates')->middleware('permission:workspace.manage');
        Route::get('/data-privacy', [SettingsController::class, 'dataPrivacyIndex'])->name('data-privacy')->middleware('permission:workspace.manage');
    });

    // API routes
    Route::get('/api/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/api/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/api/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::get('/api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/api/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/api/settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/api/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Admin-only user management
Route::middleware(['auth', 'active', 'session.valid', 'password.changed', 'permission:users.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/resend-invitation', [AdminUserController::class, 'resendInvitation'])->name('users.resend-invitation');
    Route::post('/users/{user}/regenerate-password', [AdminUserController::class, 'regeneratePassword'])->name('users.regenerate-password');
    Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/revoke-sessions', [AdminUserController::class, 'revokeSessions'])->name('users.revoke-sessions');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/invitations/{invitation}/revoke', [AdminUserController::class, 'revokeInvitation'])->name('users.invitations.revoke');
    Route::post('/invitations/{invitation}/copy-link', [AdminUserController::class, 'copyInvitationLink'])->name('users.invitations.copy-link');
});
