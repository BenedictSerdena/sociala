<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ notifications: Object });

function notificationLink(n) {
    if (n.type === 'follow') return route('profile.show', { user: n.data.user_username });
    if (n.data.post_id) return route('feed');
    return '#';
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900 text-lg">Notifications</h2>
            </div>

            <div v-if="notifications.data.length === 0" class="p-8 text-center text-gray-400">
                No notifications yet.
            </div>

            <a v-for="n in notifications.data" :key="n.id"
               :href="notificationLink(n)"
               class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 border-b border-gray-50 transition-colors"
               :class="!n.read_at ? 'bg-indigo-50' : ''">
                <img :src="n.data.user_avatar" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">{{ n.data.message }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ new Date(n.created_at).toLocaleDateString() }}</p>
                </div>
                <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
            </a>

            <div class="flex gap-2 justify-center p-4">
                <a v-if="notifications.prev_page_url" :href="notifications.prev_page_url"
                   class="px-4 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">← Prev</a>
                <a v-if="notifications.next_page_url" :href="notifications.next_page_url"
                   class="px-4 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">Next →</a>
            </div>
        </div>
    </AppLayout>
</template>
