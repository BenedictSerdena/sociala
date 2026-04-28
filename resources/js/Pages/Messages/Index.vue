<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

defineProps({ conversations: Array });

function formatTime(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff}s`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function truncate(str, len = 38) {
    if (!str) return '';
    return str.length > len ? str.slice(0, len) + '…' : str;
}
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
                      class="flex items-center gap-3.5 px-5 py-3.5 hover:bg-gray-50 border-b border-gray-50/80 transition-colors group">
                    <img :src="user.avatar_url" class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100 group-hover:ring-indigo-200 transition-all flex-shrink-0" />

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors truncate">{{ user.name }}</p>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ formatTime(user.last_message_at) }}</span>
                        </div>
                        <p class="text-xs mt-0.5 truncate"
                           :class="user.unread_count > 0 ? 'text-gray-700 font-semibold' : 'text-gray-400'">
                            {{ user.last_message ? truncate(user.last_message) : '@' + user.username }}
                        </p>
                    </div>

                    <div v-if="user.unread_count > 0"
                         class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                        {{ user.unread_count > 9 ? '9+' : user.unread_count }}
                    </div>
                    <GIcon v-else name="ChevronRight" :size="16" class="text-gray-300 group-hover:text-indigo-400 transition-colors flex-shrink-0" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
