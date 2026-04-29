<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Admin',
            'username' => 'admin',
            'email'    => 'admin@admin.com',
            'password' => 'adminpassword',
            'is_admin' => true,
        ]);

        $alice = User::create([
            'name' => 'Alice Santos',
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'password',
            'bio' => 'Photographer & traveler 📷',
        ]);

        $bob = User::create([
            'name' => 'Bob Reyes',
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password' => 'password',
            'bio' => 'Coffee lover ☕ | Developer',
        ]);

        $carol = User::create([
            'name' => 'Carol Cruz',
            'username' => 'carol',
            'email' => 'carol@example.com',
            'password' => 'password',
            'bio' => 'Designer 🎨 | Cat mom 🐱',
        ]);

        $post1 = Post::create(['user_id' => $alice->id, 'content' => 'Hello everyone! Excited to join this platform 🎉']);
        $post2 = Post::create(['user_id' => $bob->id, 'content' => 'Just had the best coffee of my life ☕ — what\'s everyone working on today?']);
        $post3 = Post::create(['user_id' => $carol->id, 'content' => 'Finished a new design project today. Feeling proud! 🎨']);
        $post4 = Post::create(['user_id' => $alice->id, 'content' => 'Beautiful sunset at the beach 🌅']);

        Like::create(['user_id' => $bob->id, 'post_id' => $post1->id]);
        Like::create(['user_id' => $carol->id, 'post_id' => $post1->id]);
        Like::create(['user_id' => $alice->id, 'post_id' => $post2->id]);

        Comment::create(['user_id' => $bob->id, 'post_id' => $post1->id, 'content' => 'Welcome! Great to have you here 🙌']);
        Comment::create(['user_id' => $carol->id, 'post_id' => $post2->id, 'content' => 'Working on a side project — where are you coding from?']);

        Follow::create(['follower_id' => $alice->id, 'following_id' => $bob->id]);
        Follow::create(['follower_id' => $alice->id, 'following_id' => $carol->id]);
        Follow::create(['follower_id' => $bob->id, 'following_id' => $alice->id]);
        Follow::create(['follower_id' => $carol->id, 'following_id' => $alice->id]);
    }
}
