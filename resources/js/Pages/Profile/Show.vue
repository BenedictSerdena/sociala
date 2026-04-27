<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    profileUser: Object,
    posts: Object,
});

const page = usePage();
const authUser = page.props.auth.user;
const isFollowing = ref(props.profileUser.is_following);
const followersCount = ref(props.profileUser.followers_count);

function toggleFollow() {
    useForm({}).post(route('follows.toggle', props.profileUser.id), {
        preserveScroll: true,
        onSuccess: () => {
            isFollowing.value = !isFollowing.value;
            followersCount.value += isFollowing.value ? 1 : -1;
        },
    });
}
</script>

<template>
    <AppLayout>
        <!-- Cover Photo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
            <div class="h-40 bg-gradient-to-r from-indigo-400 to-purple-500 relative">
                <img v-if="profileUser.cover_photo"
                     :src="`/storage/${profileUser.cover_photo}`"
                     class="w-full h-full object-cover" />
            </div>

            <div class="px-6 pb-5">
                <div class="flex items-end justify-between -mt-12 mb-3">
                    <img :src="profileUser.avatar_url"
                         class="w-24 h-24 rounded-full border-4 border-white object-cover shadow" />
                    <div class="flex gap-2 mt-auto">
                        <template v-if="authUser.id !== profileUser.id">
                            <button @click="toggleFollow"
                                    class="px-5 py-1.5 rounded-full text-sm font-medium transition-colors"
                                    :class="isFollowing ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-indigo-600 text-white hover:bg-indigo-700'">
                                {{ isFollowing ? 'Unfollow' : 'Follow' }}
                            </button>
                            <a :href="route('messages.show', profileUser.id)"
                               class="px-5 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                                Message
                            </a>
                        </template>
                        <a v-else :href="route('profile.edit')"
                           class="px-5 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Edit Profile
                        </a>
                    </div>
                </div>

                <h1 class="text-xl font-bold text-gray-900">{{ profileUser.name }}</h1>
                <p class="text-gray-500 text-sm">@{{ profileUser.username }}</p>
                <p v-if="profileUser.bio" class="text-gray-700 text-sm mt-2">{{ profileUser.bio }}</p>

                <div class="flex gap-5 mt-4 text-sm text-gray-500">
                    <span><strong class="text-gray-900">{{ profileUser.posts_count }}</strong> posts</span>
                    <span><strong class="text-gray-900">{{ followersCount }}</strong> followers</span>
                    <span><strong class="text-gray-900">{{ profileUser.following_count }}</strong> following</span>
                </div>
            </div>
        </div>

        <!-- Posts Grid -->
        <div v-if="posts.data.length === 0" class="bg-white rounded-xl p-8 text-center text-gray-400 shadow-sm border border-gray-100">
            No posts yet.
        </div>
        <div class="space-y-4">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
        </div>

        <div class="flex gap-2 justify-center mt-4">
            <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
               class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50">← Prev</a>
            <a v-if="posts.next_page_url" :href="posts.next_page_url"
               class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Next →</a>
        </div>
    </AppLayout>
</template>
