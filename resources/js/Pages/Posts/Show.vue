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
                      class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 transition-colors font-medium">
                    <GIcon name="ChevronLeft" :size="16" />
                    Back to feed
                </Link>
            </div>
            <PostCard :post="post" />
        </div>
    </AppLayout>
</template>
