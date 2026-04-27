<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ conversations: Array });
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900 text-lg">Messages</h2>
            </div>

            <div v-if="conversations.length === 0" class="p-8 text-center text-gray-400">
                No conversations yet. Follow someone and start a chat!
            </div>

            <Link v-for="user in conversations" :key="user.id"
                  :href="route('messages.show', user.id)"
                  class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 border-b border-gray-50 transition-colors">
                <img :src="user.avatar_url" class="w-11 h-11 rounded-full object-cover flex-shrink-0" />
                <div>
                    <p class="font-semibold text-gray-900 text-sm">{{ user.name }}</p>
                    <p class="text-gray-400 text-xs">@{{ user.username }}</p>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>
