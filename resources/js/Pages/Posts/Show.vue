<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import { Link } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

defineProps({ post: Object });
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-3">
            <div class="flex items-center gap-2">
                <Link :href="route('feed')"
                      class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors font-medium">
                    <GIcon name="ChevronLeft" :size="16" />
                    Back to feed
                </Link>
            </div>
            <!-- auto-open-comments tells PostCard to expand comments on mount -->
            <PostCard :post="post" :auto-open-comments="true" />
        </div>
    </AppLayout>
</template>
