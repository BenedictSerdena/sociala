<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import PostForm from '@/Components/PostForm.vue';
import Stories from '@/Components/Stories.vue';
import StoryViewer from '@/Components/StoryViewer.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    posts: Object,
    suggestions: Array,
    stories: Array,
});

const page = usePage();
const currentUser = page.props.auth.user;

const viewerOpen = ref(false);
const viewerStartIndex = ref(0);

function openStory(story) {
    const idx = props.stories.findIndex(s => s.id === story.id);
    viewerStartIndex.value = idx >= 0 ? idx : 0;
    viewerOpen.value = true;
}

function followUser(userId) {
    useForm({}).post(route('follows.toggle', userId), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>

        <!-- Story Viewer Modal -->
        <StoryViewer v-if="viewerOpen && stories.length"
                     :stories="stories"
                     :start-index="viewerStartIndex"
                     @close="viewerOpen = false" />

        <div class="flex gap-6">
            <!-- ── Main Feed (F-pattern: stories first, then posts) ── -->
            <div class="flex-1 min-w-0 max-w-xl mx-auto lg:mx-0 space-y-4">

                <!-- Stories Row -->
                <Stories :stories="stories" :current-user="currentUser" @open-story="openStory" />

                <!-- Post Form -->
                <PostForm />

                <!-- Empty state -->
                <div v-if="posts.data.length === 0"
                     class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-14 text-center">
                    <div class="text-5xl mb-4">👋</div>
                    <p class="text-gray-800 font-bold text-lg">Nothing here yet</p>
                    <p class="text-gray-400 text-sm mt-2">Follow some people or share your first post!</p>
                </div>

                <!-- Posts -->
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

                <!-- Pagination -->
                <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center pt-2 pb-4">
                    <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
                       class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all">← Prev</a>
                    <a v-if="posts.next_page_url" :href="posts.next_page_url"
                       class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all">Next →</a>
                </div>
            </div>

            <!-- ── Right Sidebar ── -->
            <div class="hidden xl:block w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-5 sticky top-6">
                    <h3 class="font-bold text-gray-800 text-sm mb-4 tracking-wide uppercase text-xs text-gray-500">Suggested for you</h3>

                    <div v-if="suggestions.length === 0" class="text-center py-6">
                        <div class="text-3xl mb-2">🎉</div>
                        <p class="text-gray-400 text-sm">You're following everyone!</p>
                    </div>

                    <div v-for="s in suggestions" :key="s.id"
                         class="flex items-center justify-between py-2.5 group">
                        <Link :href="route('profile.show', { user: s.username })" class="flex items-center gap-3 min-w-0">
                            <img :src="s.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 group-hover:ring-indigo-200 transition-all flex-shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate group-hover:text-indigo-600 transition-colors">{{ s.name }}</p>
                                <p class="text-xs text-gray-400 truncate">@{{ s.username }}</p>
                            </div>
                        </Link>
                        <button @click="followUser(s.id)"
                                class="ml-2 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex-shrink-0 px-2.5 py-1.5 rounded-lg hover:bg-indigo-50">
                            Follow
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
