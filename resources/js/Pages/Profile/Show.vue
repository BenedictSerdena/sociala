<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ profileUser: Object, posts: Object });
const page = usePage();
const authUser = page.props.auth.user;
const isFollowing = ref(props.profileUser.is_following);
const followersCount = ref(props.profileUser.followers_count);
const activeTab = ref('posts');

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
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-5">
            <!-- Cover -->
            <div class="h-44 relative">
                <div v-if="!profileUser.cover_photo"
                     class="w-full h-full bg-gradient-to-br from-indigo-400 via-purple-400 to-pink-400"></div>
                <img v-else :src="`/storage/${profileUser.cover_photo}`" class="w-full h-full object-cover" />
            </div>

            <!-- Avatar + Actions -->
            <div class="px-6 pb-5">
                <div class="flex items-end justify-between -mt-14 mb-4">
                    <div class="relative">
                        <img :src="profileUser.avatar_url"
                             class="w-28 h-28 rounded-full border-4 border-white object-cover shadow-lg" />
                    </div>

                    <div class="flex gap-2 pb-1">
                        <template v-if="authUser.id !== profileUser.id">
                            <button @click="toggleFollow"
                                    class="px-5 py-2 rounded-full text-sm font-semibold transition-all shadow-sm"
                                    :class="isFollowing
                                        ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:from-indigo-700 hover:to-purple-700 shadow-md'">
                                {{ isFollowing ? 'Following' : 'Follow' }}
                            </button>
                            <Link :href="route('messages.show', profileUser.id)"
                                  class="px-5 py-2 rounded-full text-sm font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm transition-all">
                                Message
                            </Link>
                        </template>
                        <Link v-else :href="route('profile.edit')"
                              class="px-5 py-2 rounded-full text-sm font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm transition-all">
                            Edit Profile
                        </Link>
                    </div>
                </div>

                <h1 class="text-xl font-bold text-gray-900">{{ profileUser.name }}</h1>
                <p class="text-gray-500 text-sm font-medium">@{{ profileUser.username }}</p>
                <p v-if="profileUser.bio" class="text-gray-700 text-sm mt-2 leading-relaxed">{{ profileUser.bio }}</p>

                <div class="flex gap-6 mt-4">
                    <div class="text-center">
                        <p class="font-bold text-gray-900 text-lg">{{ profileUser.posts_count }}</p>
                        <p class="text-gray-500 text-xs font-medium">Posts</p>
                    </div>
                    <div class="text-center">
                        <p class="font-bold text-gray-900 text-lg">{{ followersCount }}</p>
                        <p class="text-gray-500 text-xs font-medium">Followers</p>
                    </div>
                    <div class="text-center">
                        <p class="font-bold text-gray-900 text-lg">{{ profileUser.following_count }}</p>
                        <p class="text-gray-500 text-xs font-medium">Following</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts -->
        <div v-if="posts.data.length === 0"
             class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="text-4xl mb-3">📸</div>
            <p class="text-gray-700 font-semibold">No posts yet</p>
            <p v-if="authUser.id === profileUser.id" class="text-gray-400 text-sm mt-1">
                Share your first post from the <Link :href="route('feed')" class="text-indigo-600 hover:underline">feed</Link>!
            </p>
        </div>

        <div class="space-y-4">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
        </div>

        <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center mt-4">
            <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
               class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm">← Prev</a>
            <a v-if="posts.next_page_url" :href="posts.next_page_url"
               class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm">Next →</a>
        </div>
    </AppLayout>
</template>
