<?php

use App\Http\Controllers\WebsiteSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;
use App\Models\Notification;
use App\Services\CentrifugoService;
use Buki\AutoRoute\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/api/content/{slug?}', [PublicController::class, 'api'])->name('api.content');

Route::middleware('auth')->post('/centrifugo/token', function (Request $request) {
    if (! config('langkahkecil.notification_enable')) {
        return response()->json(['token' => 'disabled']);
    }

    $centrifugo = app(CentrifugoService::class);
    $user = Auth::user();

    if ($request->input('channel')) {
        return response()->json([
            'token' => $centrifugo->generateSubscriptionToken((string) $user->id, $request->input('channel')),
        ]);
    }

    return response()->json([
        'token' => $centrifugo->generateConnectionToken((string) $user->id),
    ]);
});

Route::middleware(['auth', 'verified', 'access'])->group(function () {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::auto('/user', 'UsersController', ['name' => 'user']);

    Route::get('/native-bridge-test', function () {
        return view('pages.settings.native-bridge-test');
    })->name('native-bridge-test');

    Route::get('/settings/website', [WebsiteSettingController::class, 'index'])->name('settings.website');
    Route::post('/settings/website', [WebsiteSettingController::class, 'save'])->name('settings.website.save');

    Route::prefix('notifications-web')->group(function () {
        Route::get('/', function (Request $request) {
            $notifications = Notification::where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->limit($request->input('limit', 50))
                ->get();

            $unreadCount = Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->count();

            return response()->json([
                'notifications' => $notifications->map(fn ($n) => [
                    'id' => $n->id,
                    'icon' => $n->icon,
                    'iconColor' => $n->icon_color,
                    'title' => $n->title,
                    'body' => $n->body,
                    'url' => $n->url,
                    'type' => $n->type,
                    'read' => $n->read,
                    'time' => $n->created_at?->diffForHumans() ?? '',
                    'created_at' => $n->created_at->toIso8601String(),
                ]),
                'unread_count' => $unreadCount,
            ]);
        });

        Route::put('/{id}/read', function (int $id) {
            $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
            $notification->update(['read' => true]);

            return response()->json(['message' => 'Marked as read']);
        });

        Route::put('/read-all', function () {
            Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json(['message' => 'All marked as read']);
        });
    });
});

Route::get('/services', [PublicController::class, 'services'])->name('services');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::get('/blog', [PublicController::class, 'blog'])->name('blog');
Route::get('/blog/category/{slug}', [PublicController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [PublicController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [PublicController::class, 'post'])->name('blog.post');
Route::get('/search', [PublicController::class, 'search'])->name('search');

Route::get('/documentation', [PublicController::class, 'documentation'])->name('documentation');
Route::get('/documentation/category/{slug}', [PublicController::class, 'documentationCategory'])->name('documentation.category');
Route::get('/documentation/tag/{slug}', [PublicController::class, 'documentationTag'])->name('documentation.tag');
Route::get('/documentation/{slug}', [PublicController::class, 'documentationShow'])->name('documentation.show');

Route::get('/{slug}', [PublicController::class, 'page'])->name('page');

require __DIR__.'/settings.php';
