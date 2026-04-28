<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

defineProps({ notifications: Object });

function notificationLink(n) {
    if (n.type === 'follow') return route('profile.show', { user: n.data.user_username });
    if (n.data.post_id) return route('posts.show', n.data.post_id);
    return '#';
}

const typeConfig = {
    like: { icon: 'Heart', color: 'text-red-500 bg-red-50' },
    comment: { icon: 'Annotation', color: 'text-indigo-500 bg-indigo-50' },
    follow: { icon: 'UserPlus', color: 'text-green-500 bg-green-50' },
};

function formatDate(date) {
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900 text-lg">Notifications</h2>
                    <p class="text-gray-400 text-xs mt-0.5">Activity on your posts</p>
                </div>

                <div v-if="notifications.data.length === 0" class="p-14 text-center">
                    <div class="text-5xl mb-4">🔔</div>
                    <p class="text-gray-800 font-bold">All caught up!</p>
                    <p class="text-gray-400 text-sm mt-2">No notifications yet.</p>
                </div>

                <a v-for="n in notifications.data" :key="n.id"
                   :href="notificationLink(n)"
                   class="flex items-center gap-4 px-5 py-4 border-b border-gray-50/80 hover:bg-gray-50 transition-colors group"
                   :class="!n.read_at ? 'bg-indigo-50/40' : ''">

                    <div class="relative flex-shrink-0">
                        <img :src="n.data.user_avatar"
                             class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100 group-hover:ring-indigo-200 transition-all"
                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${n.data.user_name}&background=6366f1&color=fff`" />
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center shadow-sm border-2 border-white"
                             :class="(typeConfig[n.type] || typeConfig.like).color">
                            <GIcon :name="(typeConfig[n.type] || typeConfig.like).icon" :size="12" />
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 leading-snug">
                            <span class="font-bold">{{ n.data.user_name }}</span>
                            {{ ' ' + n.data.message.replace(n.data.user_name, '').trim() }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1 font-medium">{{ formatDate(n.created_at) }}</p>
                    </div>

                    <div v-if="!n.read_at" class="w-2.5 h-2.5 rounded-full bg-indigo-500 flex-shrink-0"></div>
                </a>

                <div v-if="notifications.prev_page_url || notifications.next_page_url" class="flex gap-2 justify-center p-4">
                    <a v-if="notifications.prev_page_url" :href="notifications.prev_page_url"
                       class="px-5 py-2 bg-gray-100 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors">← Prev</a>
                    <a v-if="notifications.next_page_url" :href="notifications.next_page_url"
                       class="px-5 py-2 bg-gray-100 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors">Next →</a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
