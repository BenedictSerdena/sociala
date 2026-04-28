<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';
import axios from 'axios';

const props = defineProps({ notifications: Object });

const items = ref([...props.notifications.data]);
const allRead = ref(items.value.every(n => n.read_at));

function notificationLink(n) {
    if (n.type === 'follow') return route('profile.show', { user: n.data.user_username });
    if (n.type === 'story_like') return '#';
    if (n.data.post_id) return route('posts.show', n.data.post_id);
    return '#';
}

const typeConfig = {
    like:       { icon: 'Heart',      color: 'text-red-500 bg-red-50 dark:bg-red-950/50' },
    story_like: { icon: 'Heart',      color: 'text-pink-500 bg-pink-50 dark:bg-pink-950/50' },
    comment:    { icon: 'Annotation', color: 'text-indigo-500 bg-indigo-50 dark:bg-indigo-950/50' },
    follow:     { icon: 'UserPlus',   color: 'text-green-500 bg-green-50 dark:bg-green-950/50' },
};

function formatDate(date) {
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

async function markAllRead() {
    await axios.post(route('notifications.markAllRead'));
    items.value = items.value.map(n => ({ ...n, read_at: n.read_at ?? new Date().toISOString() }));
    allRead.value = true;
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white text-lg">Notifications</h2>
                        <p class="text-gray-400 text-xs mt-0.5">Activity on your posts</p>
                    </div>
                    <button v-if="!allRead && items.length > 0"
                            @click="markAllRead"
                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline transition-colors">
                        Mark all as read
                    </button>
                </div>

                <div v-if="items.length === 0" class="p-14 text-center">
                    <div class="text-5xl mb-4">🔔</div>
                    <p class="text-gray-800 dark:text-gray-200 font-bold">All caught up!</p>
                    <p class="text-gray-400 text-sm mt-2">No notifications yet.</p>
                </div>

                <a v-for="n in items" :key="n.id"
                   :href="notificationLink(n)"
                   class="flex items-center gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group"
                   :class="!n.read_at ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : ''">

                    <div class="relative flex-shrink-0">
                        <img :src="n.data.user_avatar"
                             class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700 group-hover:ring-indigo-200 transition-all"
                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${n.data.user_name}&background=6366f1&color=fff`" />
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center shadow-sm border-2 border-white dark:border-gray-900"
                             :class="(typeConfig[n.type] || typeConfig.like).color">
                            <GIcon :name="(typeConfig[n.type] || typeConfig.like).icon" :size="12" />
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 dark:text-gray-200 leading-snug">
                            <span class="font-bold">{{ n.data.user_name }}</span>
                            {{ ' ' + n.data.message.replace(n.data.user_name, '').trim() }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1 font-medium">{{ formatDate(n.created_at) }}</p>
                    </div>

                    <div v-if="!n.read_at" class="w-2.5 h-2.5 rounded-full bg-indigo-500 flex-shrink-0"></div>
                </a>

                <div v-if="notifications.prev_page_url || notifications.next_page_url" class="flex gap-2 justify-center p-4">
                    <a v-if="notifications.prev_page_url" :href="notifications.prev_page_url"
                       class="px-5 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">← Prev</a>
                    <a v-if="notifications.next_page_url" :href="notifications.next_page_url"
                       class="px-5 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">Next →</a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
