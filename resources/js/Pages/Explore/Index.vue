<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import GIcon from '@/Components/GIcon.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ trending: Object, suggested: Array });

function followUser(userId) {
    useForm({}).post(route('follows.toggle', userId), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="flex gap-6">
            <!-- Main: Trending posts -->
            <div class="flex-1 min-w-0 max-w-xl mx-auto lg:mx-0 space-y-4">

                <!-- Header -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm px-5 py-4">
                    <h2 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <span class="text-lg">🔥</span>
                        Trending this week
                    </h2>
                    <p class="text-gray-400 text-xs mt-0.5">Most liked posts from the last 7 days</p>
                </div>

                <div v-if="trending.data.length === 0"
                     class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-12 text-center">
                    <div class="text-4xl mb-3">📭</div>
                    <p class="text-gray-800 font-semibold text-sm">Nothing trending yet</p>
                    <p class="text-gray-400 text-xs mt-1">Be the first to post something!</p>
                </div>

                <PostCard v-for="post in trending.data" :key="post.id" :post="post" />

                <div v-if="trending.prev_page_url || trending.next_page_url" class="flex gap-2 justify-center pb-4">
                    <a v-if="trending.prev_page_url" :href="trending.prev_page_url"
                       class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all">← Prev</a>
                    <a v-if="trending.next_page_url" :href="trending.next_page_url"
                       class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all">Next →</a>
                </div>
            </div>

            <!-- Right sidebar: Who to follow -->
            <div class="hidden xl:block w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-5 sticky top-6">
                    <h3 class="font-bold text-xs text-gray-500 uppercase tracking-wide mb-4">Who to follow</h3>

                    <div v-if="suggested.length === 0" class="text-center py-4">
                        <p class="text-gray-400 text-sm">You're following everyone!</p>
                    </div>

                    <div v-for="u in suggested" :key="u.id" class="flex items-center justify-between py-2.5 group">
                        <Link :href="route('profile.show', { user: u.username })" class="flex items-center gap-3 min-w-0">
                            <img :src="u.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 group-hover:ring-indigo-200 transition-all flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate group-hover:text-indigo-600 transition-colors">{{ u.name }}</p>
                                <p class="text-xs text-gray-400">{{ u.followers_count }} followers</p>
                            </div>
                        </Link>
                        <button @click="followUser(u.id)"
                                class="ml-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 px-2.5 py-1.5 rounded-lg hover:bg-indigo-50 transition-colors flex-shrink-0">
                            Follow
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
