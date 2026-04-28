<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import GIcon from '@/Components/GIcon.vue';

defineProps({ posts: Object });
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm px-5 py-4">
                <h2 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                    <GIcon name="Bookmark" :size="18" :filled="true" class="text-indigo-500" />
                    Saved Posts
                </h2>
                <p class="text-gray-400 text-xs mt-0.5">Posts you've bookmarked</p>
            </div>

            <div v-if="posts.data.length === 0"
                 class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center mx-auto mb-3">
                    <GIcon name="Bookmark" :size="22" class="text-indigo-400" />
                </div>
                <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">No saved posts yet</p>
                <p class="text-gray-400 text-xs mt-1">Tap the bookmark icon on any post to save it here.</p>
            </div>

            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

            <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center pb-4">
                <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
                   class="px-5 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 shadow-sm transition-all">← Prev</a>
                <a v-if="posts.next_page_url" :href="posts.next_page_url"
                   class="px-5 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 shadow-sm transition-all">Next →</a>
            </div>
        </div>
    </AppLayout>
</template>
