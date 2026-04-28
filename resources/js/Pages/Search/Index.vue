<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({ users: [Object, Array], query: String });

const search = ref(props.query ?? '');
const following = ref({});

let debounce = null;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('search'), { q: val }, { preserveState: true, replace: true });
    }, 400);
});

function toggleFollow(userId) {
    following.value[userId] = !following.value[userId];
    useForm({}).post(route('follows.toggle', userId), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto space-y-4">

            <!-- Search input -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm px-4 py-3">
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus-within:ring-2 focus-within:ring-indigo-300 focus-within:border-transparent transition-all">
                    <GIcon name="Search" :size="18" class="text-gray-400 flex-shrink-0" />
                    <input v-model="search" type="text" placeholder="Search people by name or username…"
                           class="flex-1 bg-transparent text-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 outline-none" autofocus />
                    <button v-if="search" @click="search = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <GIcon name="Close" :size="16" />
                    </button>
                </div>
            </div>

            <!-- Empty query state -->
            <div v-if="!query" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                    <GIcon name="Search" :size="22" class="text-gray-400" />
                </div>
                <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">Find people</p>
                <p class="text-gray-400 text-xs mt-1">Search by name or username to discover people to follow.</p>
            </div>

            <!-- No results -->
            <div v-else-if="!users || (Array.isArray(users) ? users.length === 0 : users.data.length === 0)"
                 class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center">
                <p class="text-gray-800 dark:text-gray-200 font-semibold text-sm">No results for "{{ query }}"</p>
                <p class="text-gray-400 text-xs mt-1">Try a different name or username.</p>
            </div>

            <!-- Results -->
            <div v-else class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">
                <div v-for="u in (users.data ?? users)" :key="u.id"
                     class="flex items-center gap-4 px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group">
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
                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'
                                : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm hover:from-indigo-700 hover:to-purple-700'">
                        {{ (following[u.id] !== undefined ? following[u.id] : u.is_following) ? 'Following' : 'Follow' }}
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
