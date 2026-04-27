<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import PostForm from '@/Components/PostForm.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    posts: Object,
    suggestions: Array,
});

function followUser(userId) {
    useForm({}).post(route('follows.toggle', userId), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Main Feed -->
            <div class="md:col-span-2 space-y-4">
                <PostForm />

                <div v-if="posts.data.length === 0" class="bg-white rounded-xl p-8 text-center text-gray-400 shadow-sm border border-gray-100">
                    <p class="text-lg">No posts yet!</p>
                    <p class="text-sm mt-1">Follow some users or create your first post.</p>
                </div>

                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

                <!-- Pagination -->
                <div class="flex gap-2 justify-center mt-4">
                    <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
                       class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50">← Prev</a>
                    <a v-if="posts.next_page_url" :href="posts.next_page_url"
                       class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Next →</a>
                </div>
            </div>

            <!-- Sidebar: Suggestions -->
            <div class="hidden md:block">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sticky top-20">
                    <h3 class="font-semibold text-gray-700 text-sm mb-3">Who to Follow</h3>
                    <div v-if="suggestions.length === 0" class="text-gray-400 text-sm">You're following everyone!</div>
                    <div v-for="suggestion in suggestions" :key="suggestion.id" class="flex items-center justify-between py-2">
                        <Link :href="route('profile.show', { user: suggestion.username })" class="flex items-center gap-2 min-w-0">
                            <img :src="suggestion.avatar_url" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ suggestion.name }}</p>
                                <p class="text-xs text-gray-400 truncate">@{{ suggestion.username }}</p>
                            </div>
                        </Link>
                        <button @click="followUser(suggestion.id)"
                                class="ml-2 text-xs bg-indigo-600 text-white px-3 py-1 rounded-full hover:bg-indigo-700 flex-shrink-0">
                            Follow
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
