<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({
    users: [Object, Array],
    posts: [Object, Array],
    query: String,
    tab: String,
});

const search = ref(props.query ?? '');
const activeTab = ref(props.tab ?? 'users');
const following = ref({});

let debounce = null;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('search'), { q: val, tab: activeTab.value }, { preserveState: true, replace: true });
    }, 400);
});

function switchTab(tab) {
    activeTab.value = tab;
    router.get(route('search'), { q: search.value, tab }, { preserveState: true, replace: true });
}

function toggleFollow(userId) {
    following.value[userId] = !following.value[userId];
    useForm({}).post(route('follows.toggle', userId), { preserveScroll: true });
}

function formatDate(date) {
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const userList = () => Array.isArray(props.users) ? props.users : (props.users?.data ?? []);
const postList = () => Array.isArray(props.posts) ? props.posts : (props.posts?.data ?? []);
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-4">

            <!-- Search input -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm px-4 py-3">
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus-within:ring-2 focus-within:ring-indigo-300 focus-within:border-transparent transition-all">
                    <GIcon name="Search" :size="18" class="text-gray-400 flex-shrink-0" />
                    <input v-model="search" type="text" placeholder="Search people or posts…"
                           class="flex-1 bg-transparent text-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 outline-none" autofocus />
                    <button v-if="search" @click="search = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <GIcon name="Close" :size="16" />
                    </button>
                </div>
            </div>

            <!-- Tabs (only show when query exists) -->
            <div v-if="query" class="flex gap-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-1.5">
                <button v-for="t in ['users', 'posts']" :key="t"
                        @click="switchTab(t)"
                        class="flex-1 py-2 rounded-xl text-sm font-semibold capitalize transition-all"
                        :class="activeTab === t
                            ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'">
                    {{ t }}
                </button>
            </div>

            <!-- Empty query state -->
            <div v-if="!query" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                    <GIcon name="Search" :size="22" class="text-gray-400" />
                </div>
                <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">Search people &amp; posts</p>
                <p class="text-gray-400 text-xs mt-1">Find users by name or username, or search post content.</p>
            </div>

            <!-- Users tab -->
            <template v-else-if="activeTab === 'users'">
                <div v-if="userList().length === 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                    <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">No users for "{{ query }}"</p>
                    <button @click="switchTab('posts')" class="text-indigo-600 text-xs mt-2 hover:underline">Try searching posts instead</button>
                </div>

                <div v-else class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div v-for="u in userList()" :key="u.id"
                         class="flex items-center gap-4 px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group last:border-0">
                        <Link :href="route('profile.show', { user: u.username })" class="flex items-center gap-3 flex-1 min-w-0">
                            <img :src="u.avatar_url" class="w-11 h-11 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700 group-hover:ring-indigo-200 transition-all flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="font-bold text-gray-900 dark:text-white text-sm truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ u.name }}</p>
                                <p class="text-gray-400 text-xs">@{{ u.username }}
                                    <span v-if="u.followers_count" class="ml-1">· {{ u.followers_count }} follower{{ u.followers_count !== 1 ? 's' : '' }}</span>
                                </p>
                            </div>
                        </Link>
                        <button @click="toggleFollow(u.id)"
                                class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all"
                                :class="(following[u.id] !== undefined ? following[u.id] : u.is_following)
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-200'
                                    : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm hover:from-indigo-700 hover:to-purple-700'">
                            {{ (following[u.id] !== undefined ? following[u.id] : u.is_following) ? 'Following' : 'Follow' }}
                        </button>
                    </div>
                </div>
            </template>

            <!-- Posts tab -->
            <template v-else-if="activeTab === 'posts'">
                <div v-if="postList().length === 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                    <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">No posts for "{{ query }}"</p>
                    <button @click="switchTab('users')" class="text-indigo-600 text-xs mt-2 hover:underline">Try searching people instead</button>
                </div>

                <div v-else class="space-y-3">
                    <Link v-for="p in postList()" :key="p.id"
                          :href="route('posts.show', p.id)"
                          class="block bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm px-5 py-4 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all group">
                        <div class="flex items-center gap-3 mb-3">
                            <img :src="p.user.avatar_url" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ p.user.name }}</p>
                                <p class="text-xs text-gray-400">@{{ p.user.username }} · {{ formatDate(p.created_at) }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ p.content }}</p>
                        <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                            <span>❤️ {{ p.likes_count }}</span>
                            <span>💬 {{ p.comments_count }}</span>
                        </div>
                    </Link>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
