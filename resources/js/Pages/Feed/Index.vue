<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import PostForm from '@/Components/PostForm.vue';
import Stories from '@/Components/Stories.vue';
import StoryViewer from '@/Components/StoryViewer.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    posts: Object,
    suggestions: Array,
    stories: Array,
});

const page = usePage();
const currentUser = page.props.auth.user;

const storiesList = ref([...props.stories]);
const viewerOpen = ref(false);
const viewerStartIndex = ref(0);

// ── Infinite scroll ──
const allPosts = ref([...props.posts.data]);
const nextPageUrl = ref(props.posts.next_page_url);
const loadingMore = ref(false);
const sentinel = ref(null);

watch(() => props.posts, (newPosts) => {
    if (loadingMore.value) {
        allPosts.value.push(...newPosts.data);
        nextPageUrl.value = newPosts.next_page_url;
        loadingMore.value = false;
    }
}, { deep: true });

async function loadMore() {
    if (!nextPageUrl.value || loadingMore.value) return;
    loadingMore.value = true;
    router.get(nextPageUrl.value, {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['posts'],
    });
}

onMounted(() => {
    if (sentinel.value) {
        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) loadMore();
        }, { rootMargin: '200px' });
        observer.observe(sentinel.value);
    }

    window.Echo.private(`feed.${currentUser.id}`)
        .listen('StoryPosted', (story) => {
            const existingIdx = storiesList.value.findIndex(s => s.user.id === story.user.id);
            if (existingIdx >= 0) {
                storiesList.value[existingIdx] = story;
            } else {
                storiesList.value.unshift(story);
            }
        });
});

onUnmounted(() => {
    window.Echo.leave(`feed.${currentUser.id}`);
});

function openStory(story) {
    const idx = storiesList.value.findIndex(s => s.id === story.id);
    viewerStartIndex.value = idx >= 0 ? idx : 0;
    viewerOpen.value = true;
}

function onStoryDeleted(storyId) {
    storiesList.value = storiesList.value.filter(s => s.id !== storyId);
}

function onStoryArchived(storyId) {
    storiesList.value = storiesList.value.filter(s => s.id !== storyId);
}

function followUser(userId) {
    useForm({}).post(route('follows.toggle', userId), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>

        <StoryViewer v-if="viewerOpen && storiesList.length"
                     :stories="storiesList"
                     :start-index="viewerStartIndex"
                     @close="viewerOpen = false"
                     @deleted="onStoryDeleted"
                     @archived="onStoryArchived" />

        <div class="flex gap-6">
            <!-- ── Main Feed ── -->
            <div class="flex-1 min-w-0 max-w-xl mx-auto lg:mx-0 space-y-4">

                <Stories :stories="storiesList" :current-user="currentUser" @open-story="openStory" />

                <PostForm />

                <!-- Empty state -->
                <div v-if="allPosts.length === 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-14 text-center">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-950 dark:to-purple-950 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-800 dark:text-white font-bold text-lg mb-1">Your feed is empty</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mb-5">Follow people to see their posts here</p>
                    <Link :href="route('explore')"
                          class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-full hover:opacity-90 transition-all shadow-sm">
                        Explore people to follow
                    </Link>
                </div>

                <!-- Posts -->
                <PostCard v-for="post in allPosts" :key="post.id" :post="post" />

                <!-- Infinite scroll sentinel + loader -->
                <div ref="sentinel" class="py-4 flex justify-center">
                    <div v-if="loadingMore" class="flex items-center gap-2 text-gray-400 dark:text-gray-500 text-sm">
                        <div class="w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
                        Loading more…
                    </div>
                    <p v-else-if="!nextPageUrl && allPosts.length > 0" class="text-gray-300 dark:text-gray-600 text-xs">
                        You're all caught up ✓
                    </p>
                </div>
            </div>

            <!-- ── Right Sidebar ── -->
            <div class="hidden xl:block w-72 flex-shrink-0">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-5 sticky top-6">
                    <h3 class="font-bold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Suggested for you</h3>

                    <div v-if="suggestions.length === 0" class="text-center py-6">
                        <div class="text-3xl mb-2">🎉</div>
                        <p class="text-gray-400 dark:text-gray-500 text-sm">You're following everyone!</p>
                    </div>

                    <div v-for="s in suggestions" :key="s.id"
                         class="flex items-center justify-between py-2.5 group">
                        <Link :href="route('profile.show', { user: s.username })" class="flex items-center gap-3 min-w-0">
                            <img :src="s.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700 group-hover:ring-indigo-200 transition-all flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ s.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">@{{ s.username }}</p>
                            </div>
                        </Link>
                        <button @click="followUser(s.id)"
                                class="ml-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors flex-shrink-0 px-2.5 py-1.5 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950">
                            Follow
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
