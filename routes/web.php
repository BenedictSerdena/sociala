<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\PostController as AdminPost;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoryController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');

    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/report', [CommentController::class, 'report'])->name('comments.report');
    Route::post('/comments/{comment}/pin', [CommentController::class, 'pin'])->name('comments.pin');
    Route::post('/comments/{comment}/hide', [CommentController::class, 'hide'])->name('comments.hide');

    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('likes.toggle');
    Route::post('/posts/{post}/bookmark', [BookmarkController::class, 'toggle'])->name('bookmarks.toggle');
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');

    Route::post('/users/{user}/follow', [FollowController::class, 'toggle'])->name('follows.toggle');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user}', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{user}/read', [MessageController::class, 'markRead'])->name('messages.read');
    Route::delete('/message/{message}/me', [MessageController::class, 'deleteForMe'])->name('messages.deleteForMe');
    Route::delete('/message/{message}/everyone', [MessageController::class, 'deleteForEveryone'])->name('messages.deleteForEveryone');
    Route::post('/message/{message}/pin', [MessageController::class, 'pin'])->name('messages.pin');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread');
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/users/{user:username}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/users/{user:username}/followers', [ProfileController::class, 'followers'])->name('profile.followers');
    Route::get('/users/{user:username}/following', [ProfileController::class, 'following'])->name('profile.following');

    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
    Route::get('/hashtags/{tag}', [HashtagController::class, 'show'])->name('hashtags.show');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/call/token', [\App\Http\Controllers\CallController::class, 'token'])->name('call.token');
    Route::post('/call/signal', [\App\Http\Controllers\CallController::class, 'signal'])->name('call.signal');

    Route::post('/stories', [StoryController::class, 'store'])->name('stories.store');
    Route::post('/stories/{story}/archive', [StoryController::class, 'archive'])->name('stories.archive');
    Route::delete('/stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');

    Route::post('/posts/{post}/visibility', [PostController::class, 'setVisibility'])->name('posts.visibility');

    Route::get('/users/{user:username}/archived-stories', [ProfileController::class, 'archivedStories'])->name('profile.archived-stories');
    Route::get('/users/{user:username}/archived-posts', [ProfileController::class, 'archived'])->name('profile.archived');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUser::class, 'index'])->name('users.index');
    Route::post('/users/bulk-action', [AdminUser::class, 'bulkAction'])->name('users.bulk');
    Route::get('/users/{user}', [AdminUser::class, 'show'])->name('users.show');
    Route::post('/users/{user}/ban', [AdminUser::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/promote', [AdminUser::class, 'promote'])->name('users.promote');
    Route::delete('/users/{user}', [AdminUser::class, 'destroy'])->name('users.destroy');
    Route::get('/reports', [AdminReport::class, 'index'])->name('reports.index');
    Route::post('/reports/{report}/dismiss', [AdminReport::class, 'dismiss'])->name('reports.dismiss');
    Route::delete('/reports/{report}/comment', [AdminReport::class, 'deleteComment'])->name('reports.deleteComment');
    Route::get('/posts', [AdminPost::class, 'index'])->name('posts.index');
    Route::delete('/posts/{post}', [AdminPost::class, 'destroy'])->name('posts.destroy');
});

require __DIR__ . '/auth.php';
