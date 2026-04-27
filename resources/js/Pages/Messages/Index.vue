<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

defineProps({ conversations: Array });
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900 text-lg">Messages</h2>
                    <p class="text-gray-400 text-xs mt-0.5">Your direct conversations</p>
                </div>

                <div v-if="conversations.length === 0" class="p-14 text-center">
                    <div class="text-5xl mb-4">💬</div>
                    <p class="text-gray-800 font-bold">No conversations yet</p>
                    <p class="text-gray-400 text-sm mt-2">Follow someone and start a chat!</p>
                </div>

                <Link v-for="user in conversations" :key="user.id"
                      :href="route('messages.show', user.id)"
                      class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 border-b border-gray-50/80 transition-colors group">
                    <div class="relative flex-shrink-0">
                        <img :src="user.avatar_url" class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100 group-hover:ring-indigo-200 transition-all" />
                        <div class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-400 rounded-full border-2 border-white"></div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">{{ user.name }}</p>
                        <p class="text-gray-400 text-xs">@{{ user.username }}</p>
                    </div>
                    <GIcon name="ChevronRight" :size="16" class="text-gray-300 group-hover:text-indigo-400 transition-colors flex-shrink-0" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
