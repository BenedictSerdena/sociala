<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PostCard from '@/Components/PostCard.vue';
import ImageLightbox from '@/Components/ImageLightbox.vue';
import GIcon from '@/Components/GIcon.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({ profileUser: Object, posts: Object });
const page = usePage();
const authUser = page.props.auth.user;
const isOwnProfile = authUser.id === props.profileUser.id;
const isFollowing = ref(props.profileUser.is_following);
const followersCount = ref(props.profileUser.followers_count);
const view = ref('grid');
const lightboxSrc = ref(null);

// Archived posts (own profile)
const archivedPosts = ref([]);
const archivedLoaded = ref(false);
const archivedLoading = ref(false);

async function loadArchived() {
    if (archivedLoaded.value) return;
    archivedLoading.value = true;
    const res = await axios.get(route('profile.archived', { user: props.profileUser.username }));
    archivedPosts.value = res.data.data ?? res.data;
    archivedLoaded.value = true;
    archivedLoading.value = false;
}

function switchView(v) {
    view.value = v;
    if (v === 'archived') loadArchived();
}

// Followers / Following modal
const peopleModal = ref(null);
const peopleList = ref([]);
const peopleLoading = ref(false);

async function openPeople(type) {
    peopleModal.value = type;
    peopleLoading.value = true;
    peopleList.value = [];
    try {
        const res = await axios.get(route(`profile.${type}`, { user: props.profileUser.username }));
        peopleList.value = res.data;
    } finally {
        peopleLoading.value = false;
    }
}

function closePeople() { peopleModal.value = null; }

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
        <div class="max-w-xl mx-auto space-y-0">

            <!-- ── Profile Header ── -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden mb-3">

                <!-- Cover photo — taller -->
                <div class="h-52 relative">
                    <div v-if="!profileUser.cover_photo"
                         class="w-full h-full bg-gradient-to-br from-indigo-400 via-purple-500 to-pink-400"></div>
                    <img v-else :src="`/storage/${profileUser.cover_photo}`" class="w-full h-full object-cover" />
                </div>

                <div class="px-4 pt-0 pb-4">

                    <!-- Avatar + buttons row -->
                    <div class="flex items-end justify-between -mt-11 mb-3">
                        <div class="relative">
                            <img :src="profileUser.avatar_url"
                                 class="w-[88px] h-[88px] rounded-full border-4 border-white dark:border-gray-900 object-cover shadow-lg" />
                        </div>

                        <div class="flex gap-2 pt-12">
                            <template v-if="authUser.id !== profileUser.id">
                                <button @click="toggleFollow"
                                        class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold transition-all shadow-sm"
                                        :class="isFollowing
                                            ? 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:opacity-90'">
                                    <GIcon :name="isFollowing ? 'UserCheck' : 'UserPlus'" :size="12" />
                                    {{ isFollowing ? 'Following' : 'Follow' }}
                                </button>
                                <Link :href="route('messages.show', profileUser.id)"
                                      class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm">
                                    <GIcon name="AnnotationDots" :size="12" />
                                    Message
                                </Link>
                            </template>
                            <Link v-else :href="route('profile.edit')"
                                  class="flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm">
                                <GIcon name="Edit" :size="12" />
                                Edit profile
                            </Link>
                        </div>
                    </div>

                    <!-- Name / username / bio -->
                    <div class="mb-4">
                        <h1 class="text-[16px] font-bold text-gray-900 dark:text-white leading-tight">{{ profileUser.name }}</h1>
                        <p class="text-gray-400 dark:text-gray-500 text-xs mt-0.5">@{{ profileUser.username }}</p>
                        <p v-if="profileUser.bio" class="text-gray-600 dark:text-gray-400 text-sm mt-2 leading-relaxed max-w-xs">{{ profileUser.bio }}</p>
                    </div>

                    <!-- Stats — clickable -->
                    <div class="flex gap-4">
                        <div class="text-center">
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ profileUser.posts_count }}</p>
                            <p class="text-[11px] text-gray-400 font-medium">Posts</p>
                        </div>
                        <div class="w-px bg-gray-100 dark:bg-gray-800"></div>
                        <button @click="openPeople('followers')" class="text-center hover:opacity-70 transition-opacity">
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ followersCount }}</p>
                            <p class="text-[11px] text-gray-400 font-medium underline decoration-dotted">Followers</p>
                        </button>
                        <div class="w-px bg-gray-100 dark:bg-gray-800"></div>
                        <button @click="openPeople('following')" class="text-center hover:opacity-70 transition-opacity">
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ profileUser.following_count }}</p>
                            <p class="text-[11px] text-gray-400 font-medium underline decoration-dotted">Following</p>
                        </button>
                    </div>
                </div>

                <!-- View toggle tabs -->
                <div class="flex border-t border-gray-100 dark:border-gray-800">
                    <button @click="switchView('grid')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-semibold transition-all border-b-2"
                            :class="view === 'grid' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 3h8v8H3zm0 10h8v8H3zm10-10h8v8h-8zm0 10h8v8h-8z"/>
                        </svg>
                        Grid
                    </button>
                    <button @click="switchView('list')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-semibold transition-all border-b-2"
                            :class="view === 'list' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                        </svg>
                        Posts
                    </button>
                    <button v-if="isOwnProfile" @click="switchView('archived')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-semibold transition-all border-b-2"
                            :class="view === 'archived' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                        <GIcon name="Bookmark" :size="14" />
                        Archive
                    </button>
                </div>
            </div>

            <!-- ── GRID VIEW ── -->
            <div v-if="view === 'grid'" class="mt-0">
                <div v-if="posts.data.length === 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-12 text-center mt-3">
                    <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                        <GIcon name="Image" :size="24" class="text-gray-300" />
                    </div>
                    <p class="text-gray-700 dark:text-gray-200 font-semibold text-sm">No posts yet</p>
                    <p v-if="authUser.id === profileUser.id" class="text-gray-400 text-xs mt-1.5">
                        <Link :href="route('feed')" class="text-indigo-500 hover:underline">Share your first post</Link>
                    </p>
                </div>

                <div v-else class="grid grid-cols-3 gap-px bg-gray-200 dark:bg-gray-700 rounded-2xl overflow-hidden">
                    <div v-for="post in posts.data" :key="post.id"
                         class="relative bg-white dark:bg-gray-900 group cursor-pointer overflow-hidden"
                         style="aspect-ratio:1"
                         @click="post.image_url ? lightboxSrc = post.image_url : router.visit(route('posts.show', post.id))">

                        <img v-if="post.image_url"
                             :src="post.image_url"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />

                        <div v-else
                             class="w-full h-full flex items-center justify-center p-3 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950 dark:to-purple-950">
                            <p class="text-[11px] text-indigo-700 dark:text-indigo-300 text-center line-clamp-4 leading-relaxed font-medium">
                                {{ post.content }}
                            </p>
                        </div>

                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100">
                            <span class="text-white text-sm font-bold flex items-center gap-1 drop-shadow">
                                <GIcon name="Heart" :size="16" :filled="true" class="text-white" />
                                {{ post.likes_count }}
                            </span>
                            <span class="text-white text-sm font-bold flex items-center gap-1 drop-shadow">
                                <GIcon name="Annotation" :size="16" class="text-white" />
                                {{ post.comments_count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── LIST VIEW ── -->
            <div v-else-if="view === 'list'" class="space-y-3 mt-3">
                <div v-if="posts.data.length === 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 p-12 text-center">
                    <p class="text-gray-700 dark:text-gray-300 font-semibold text-sm">No posts yet</p>
                </div>
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
            </div>

            <!-- ── ARCHIVE VIEW ── -->
            <div v-else-if="view === 'archived'" class="space-y-3 mt-3">
                <div class="bg-purple-50 dark:bg-purple-950/30 rounded-2xl border border-purple-100 dark:border-purple-900/50 px-4 py-3 flex items-center gap-2">
                    <GIcon name="Bookmark" :size="16" class="text-purple-500 flex-shrink-0" />
                    <p class="text-purple-700 dark:text-purple-300 text-xs font-medium">Archived posts are only visible to you.</p>
                </div>
                <div v-if="archivedLoading" class="flex justify-center py-10">
                    <div class="w-6 h-6 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <div v-else-if="archivedPosts.length === 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 p-12 text-center">
                    <div class="w-12 h-12 rounded-full bg-purple-50 dark:bg-purple-950/40 flex items-center justify-center mx-auto mb-3">
                        <GIcon name="Bookmark" :size="22" class="text-purple-400" />
                    </div>
                    <p class="text-gray-700 dark:text-gray-200 font-semibold text-sm">No archived posts</p>
                    <p class="text-gray-400 text-xs mt-1">Posts you archive will appear here.</p>
                </div>
                <PostCard v-else v-for="post in archivedPosts" :key="post.id" :post="post" />
            </div>

            <ImageLightbox v-if="lightboxSrc" :src="lightboxSrc" @close="lightboxSrc = null" />

            <!-- Pagination -->
            <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center pt-4 pb-2">
                <a v-if="posts.prev_page_url" :href="posts.prev_page_url"
                   class="px-5 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all dark:text-gray-300">← Prev</a>
                <a v-if="posts.next_page_url" :href="posts.next_page_url"
                   class="px-5 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-full text-sm font-medium hover:bg-gray-50 shadow-sm transition-all dark:text-gray-300">Next →</a>
            </div>
        </div>

        <!-- ── Followers / Following Modal ── -->
        <Teleport to="body">
            <div v-if="peopleModal"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
                 @click.self="closePeople">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-gray-900 w-full sm:w-[380px] sm:rounded-2xl rounded-t-2xl max-h-[70vh] flex flex-col shadow-2xl">

                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base capitalize">{{ peopleModal }}</h3>
                        <button @click="closePeople" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                            <GIcon name="Close" :size="18" />
                        </button>
                    </div>

                    <!-- List -->
                    <div class="overflow-y-auto flex-1 px-4 py-3 space-y-1">
                        <div v-if="peopleLoading" class="flex justify-center py-10">
                            <div class="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        </div>

                        <div v-else-if="peopleList.length === 0" class="text-center py-10 text-gray-400 dark:text-gray-500 text-sm">
                            No {{ peopleModal }} yet
                        </div>

                        <Link v-for="person in peopleList" :key="person.id"
                              :href="route('profile.show', { user: person.username })"
                              @click="closePeople"
                              class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group">
                            <img :src="person.avatar_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-sm text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ person.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">@{{ person.username }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
