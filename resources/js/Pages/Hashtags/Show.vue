<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import { Link } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

defineProps({ tag: String, posts: Object });
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-4">

            <!-- Header -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-lg flex-shrink-0">
                        #
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-gray-900 dark:text-white">#{{ tag }}</h1>
                        <p class="text-gray-400 text-xs">{{ posts.total }} post{{ posts.total !== 1 ? 's' : '' }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="posts.data.length === 0"
                 class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">No posts with #{{ tag }} yet</p>
                <p class="text-gray-400 text-xs mt-1">Be the first to use this hashtag!</p>
            </div>

            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

            <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center pb-4">
                <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
                   class="px-5 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all dark:text-gray-300">← Prev</a>
                <a v-if="posts.next_page_url" :href="posts.next_page_url"
                   class="px-5 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all dark:text-gray-300">Next →</a>
            </div>
        </div>
    </AppLayout>
</template>
