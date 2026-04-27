<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import GIcon from '@/Components/GIcon.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ profileUser: Object, posts: Object });
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
        <div class="max-w-2xl mx-auto space-y-4">

            <!-- Profile Card -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">

                <!-- Cover — compact 28 height -->
                <div class="h-28 relative">
                    <div v-if="!profileUser.cover_photo"
                         class="w-full h-full bg-gradient-to-br from-indigo-400 via-purple-400 to-pink-400"></div>
                    <img v-else :src="`/storage/${profileUser.cover_photo}`" class="w-full h-full object-cover" />
                </div>

                <div class="px-5 pb-5">
                    <!-- Avatar row -->
                    <div class="flex items-end justify-between -mt-10 mb-3">
                        <img :src="profileUser.avatar_url"
                             class="w-20 h-20 rounded-full border-4 border-white object-cover shadow-md ring-2 ring-gray-100" />

                        <div class="flex gap-2">
                            <template v-if="authUser.id !== profileUser.id">
                                <button @click="toggleFollow"
                                        class="flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold transition-all"
                                        :class="isFollowing
                                            ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200'
                                            : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm hover:from-indigo-700 hover:to-purple-700'">
                                    <GIcon :name="isFollowing ? 'UserCheck' : 'UserPlus'" :size="13" />
                                    {{ isFollowing ? 'Following' : 'Follow' }}
                                </button>
                                <Link :href="route('messages.show', profileUser.id)"
                                      class="flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                                    <GIcon name="AnnotationDots" :size="13" />
                                    Message
                                </Link>
                            </template>
                            <Link v-else :href="route('profile.edit')"
                                  class="flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-all">
                                <GIcon name="Settings" :size="13" />
                                Edit Profile
                            </Link>
                        </div>
                    </div>

                    <!-- Name & bio -->
                    <div class="mb-4">
                        <h1 class="text-base font-bold text-gray-900 leading-tight">{{ profileUser.name }}</h1>
                        <p class="text-gray-400 text-xs font-medium">@{{ profileUser.username }}</p>
                        <p v-if="profileUser.bio" class="text-gray-600 text-xs mt-1.5 leading-relaxed">{{ profileUser.bio }}</p>
                    </div>

                    <!-- Stats row — inline, compact -->
                    <div class="flex gap-5 border-t border-gray-100 pt-3">
                        <div>
                            <span class="font-bold text-gray-900 text-sm">{{ profileUser.posts_count }}</span>
                            <span class="text-gray-400 text-xs ml-1">Posts</span>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 text-sm">{{ followersCount }}</span>
                            <span class="text-gray-400 text-xs ml-1">Followers</span>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 text-sm">{{ profileUser.following_count }}</span>
                            <span class="text-gray-400 text-xs ml-1">Following</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="posts.data.length === 0"
                 class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-10 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                    <GIcon name="Image" :size="22" class="text-gray-400" />
                </div>
                <p class="text-gray-800 font-semibold text-sm">No posts yet</p>
                <p v-if="authUser.id === profileUser.id" class="text-gray-400 text-xs mt-1">
                    Share your first post from the
                    <Link :href="route('feed')" class="text-indigo-600 hover:underline">feed</Link>!
                </p>
            </div>

            <!-- Posts -->
            <div class="space-y-4">
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
            </div>

            <!-- Pagination -->
            <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center pb-4">
                <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
                   class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all">← Prev</a>
                <a v-if="posts.next_page_url" :href="posts.next_page_url"
                   class="px-5 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all">Next →</a>
            </div>
        </div>
    </AppLayout>
</template>
