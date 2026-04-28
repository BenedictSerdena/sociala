<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';
import { useToast } from '@/composables/useToast.js';

const props = defineProps({ post: Object });
const emit = defineEmits(['close']);

const { show: toast } = useToast();
const authUser = usePage().props.auth.user;

const liked        = ref(props.post.is_liked ?? false);
const likesCount   = ref(props.post.likes_count ?? 0);
const bookmarked   = ref(props.post.is_bookmarked ?? false);
const likeAnimating = ref(false);

const comments        = ref([]);
const commentsLoading = ref(true);
const newComment      = ref('');
const submitting      = ref(false);
const commentInput    = ref(null);
const expandedReplies = reactive({}); // commentId -> bool

function formatDate(date) {
    const d = new Date(date);
    const diff = Math.floor((new Date() - d) / 1000);
    if (diff < 60) return `${diff}s`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function fullDate(date) {
    return new Date(date).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

async function loadComments() {
    try {
        const res = await axios.get(route('comments.index', props.post.id));
        comments.value = res.data;
    } finally { commentsLoading.value = false; }
}

async function toggleLike() {
    likeAnimating.value = true;
    setTimeout(() => likeAnimating.value = false, 350);
    const res = await axios.post(route('likes.toggle', props.post.id));
    liked.value = res.data.liked;
    likesCount.value = res.data.count;
}

async function toggleBookmark() {
    const res = await axios.post(route('bookmarks.toggle', props.post.id));
    bookmarked.value = res.data.bookmarked;
    toast(bookmarked.value ? 'Post saved' : 'Removed from saved', 'success');
}

async function submitComment() {
    if (!newComment.value.trim() || submitting.value) return;
    submitting.value = true;
    try {
        const res = await axios.post(route('comments.store', props.post.id), { content: newComment.value });
        comments.value.unshift(res.data);
        props.post.comments_count++;
        newComment.value = '';
    } finally { submitting.value = false; }
}

async function deleteComment(commentId, parentComment = null) {
    try {
        await axios.delete(route('comments.destroy', commentId));
        if (parentComment) {
            parentComment.replies = parentComment.replies.filter(r => r.id !== commentId);
        } else {
            comments.value = comments.value.filter(c => c.id !== commentId);
        }
        props.post.comments_count--;
    } catch { toast('Could not delete', 'error'); }
}

function onKey(e) {
    if (e.key === 'Escape') emit('close');
}

onMounted(() => {
    loadComments();
    window.addEventListener('keydown', onKey);
});
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <!-- Backdrop -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @click.self="emit('close')">

        <!-- Modal container -->
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden flex"
             style="width: min(90vw, 960px); height: min(88vh, 680px);">

            <!-- Close button -->
            <button @click="emit('close')"
                    class="absolute top-3 right-3 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/50 text-white transition-all">
                <GIcon name="Close" :size="16" class="text-white" />
            </button>

            <!-- LEFT — Image -->
            <div class="flex-shrink-0 bg-black flex items-center justify-center"
                 style="width: 58%;">
                <img :src="post.image_url"
                     class="w-full h-full object-contain"
                     style="max-height: min(88vh, 680px);" />
            </div>

            <!-- RIGHT — Details -->
            <div class="flex flex-col flex-1 min-w-0 border-l border-gray-100 dark:border-gray-800">

                <!-- Header -->
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
                    <Link :href="route('profile.show', { user: post.user.username })" @click="emit('close')">
                        <img :src="post.user.avatar_url"
                             class="w-9 h-9 rounded-full object-cover ring-2 ring-indigo-100 dark:ring-indigo-900" />
                    </Link>
                    <div class="flex-1 min-w-0">
                        <Link :href="route('profile.show', { user: post.user.username })" @click="emit('close')"
                              class="font-bold text-sm text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            {{ post.user.name }}
                        </Link>
                        <p class="text-[11px] text-gray-400">@{{ post.user.username }}</p>
                    </div>
                </div>

                <!-- Caption + Comments (scrollable) -->
                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4">

                    <!-- Caption -->
                    <div v-if="post.content" class="flex gap-2.5">
                        <img :src="post.user.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-0.5" />
                        <div>
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3.5 py-2.5 border border-gray-100 dark:border-gray-700 inline-block max-w-full">
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <span class="font-bold text-xs text-gray-900 dark:text-white">{{ post.user.name }}</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ post.content }}</span>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 ml-1" :title="fullDate(post.created_at)">{{ formatDate(post.created_at) }}</p>
                        </div>
                    </div>

                    <!-- Comments loading -->
                    <div v-if="commentsLoading" class="flex justify-center py-4">
                        <div class="w-5 h-5 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <p v-else-if="comments.length === 0" class="text-center text-gray-400 dark:text-gray-500 text-xs py-3">No comments yet — be the first!</p>

                    <!-- Comments list -->
                    <div v-for="comment in comments" :key="comment.id" class="flex gap-2.5 group/c">
                        <Link :href="route('profile.show', { user: comment.user?.username })" @click="emit('close')">
                            <img :src="comment.user?.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-0.5 hover:opacity-80 transition-opacity" />
                        </Link>
                        <div class="flex-1 min-w-0">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3.5 py-2.5 border border-gray-100 dark:border-gray-700 shadow-sm inline-block max-w-full"
                                 :class="comment.pinned_at ? 'border-indigo-200 dark:border-indigo-800' : ''">
                                <!-- Pinned badge -->
                                <div v-if="comment.pinned_at" class="flex items-center gap-1 mb-0.5">
                                    <svg class="w-2.5 h-2.5 text-indigo-500" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                    <span class="text-[9px] font-semibold text-indigo-500 uppercase tracking-wide">Pinned</span>
                                </div>
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <Link :href="route('profile.show', { user: comment.user?.username })" @click="emit('close')"
                                          class="font-bold text-xs text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors leading-snug">
                                        {{ comment.user?.name }}
                                    </Link>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 leading-snug">{{ comment.content }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-1 ml-1">
                                <time class="text-[10px] text-gray-400" :title="fullDate(comment.created_at)">{{ formatDate(comment.created_at) }}</time>
                                <button v-if="comment.user?.id === authUser.id || post.user.id === authUser.id"
                                        @click="deleteComment(comment.id)"
                                        class="text-[10px] text-gray-400 hover:text-red-500 transition-colors opacity-0 group-hover/c:opacity-100">
                                    Delete
                                </button>
                                <!-- View replies toggle -->
                                <button v-if="comment.replies?.length"
                                        @click="expandedReplies[comment.id] = !expandedReplies[comment.id]"
                                        class="text-[10px] font-semibold text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors flex items-center gap-1">
                                    <svg class="w-2.5 h-2.5 transition-transform" :class="expandedReplies[comment.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                    {{ expandedReplies[comment.id] ? 'Hide replies' : `View ${comment.replies.length} ${comment.replies.length === 1 ? 'reply' : 'replies'}` }}
                                </button>
                            </div>

                            <!-- Nested replies -->
                            <div v-if="expandedReplies[comment.id] && comment.replies?.length"
                                 class="mt-2.5 pl-3 border-l-2 border-indigo-100 dark:border-indigo-900/50 space-y-2.5">
                                <div v-for="reply in comment.replies" :key="reply.id" class="flex gap-2 group/r">
                                    <Link :href="route('profile.show', { user: reply.user?.username })" @click="emit('close')">
                                        <img :src="reply.user?.avatar_url" class="w-6 h-6 rounded-full object-cover flex-shrink-0 mt-0.5 hover:opacity-80 transition-opacity" />
                                    </Link>
                                    <div class="flex-1 min-w-0">
                                        <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2 border border-gray-100 dark:border-gray-700 shadow-sm inline-block max-w-full">
                                            <div class="flex flex-wrap items-baseline gap-1">
                                                <Link :href="route('profile.show', { user: reply.user?.username })" @click="emit('close')"
                                                      class="font-bold text-xs text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors leading-snug">
                                                    {{ reply.user?.name }}
                                                </Link>
                                                <span class="text-xs text-gray-700 dark:text-gray-300 leading-snug">{{ reply.content }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1 ml-0.5">
                                            <time class="text-[10px] text-gray-400" :title="fullDate(reply.created_at)">{{ formatDate(reply.created_at) }}</time>
                                            <button v-if="reply.user?.id === authUser.id || post.user.id === authUser.id"
                                                    @click="deleteComment(reply.id, comment)"
                                                    class="text-[10px] text-gray-400 hover:text-red-500 transition-colors opacity-0 group-hover/r:opacity-100">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action bar -->
                <div class="border-t border-gray-100 dark:border-gray-800 px-4 py-2.5 flex-shrink-0">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1">
                            <button @click="toggleLike"
                                    class="p-1.5 rounded-xl transition-all"
                                    :class="liked ? 'text-red-500' : 'text-gray-500 dark:text-gray-400 hover:text-red-400'">
                                <span class="transition-transform duration-200 block" :class="likeAnimating ? 'scale-125' : 'scale-100'">
                                    <GIcon name="Heart" :size="22" :filled="liked" />
                                </span>
                            </button>
                            <button @click="commentInput?.focus()"
                                    class="p-1.5 rounded-xl text-gray-500 dark:text-gray-400 hover:text-indigo-500 transition-all">
                                <GIcon name="Annotation" :size="22" />
                            </button>
                        </div>
                        <button @click="toggleBookmark"
                                class="p-1.5 rounded-xl transition-all"
                                :class="bookmarked ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 hover:text-indigo-500'">
                            <GIcon name="Bookmark" :size="20" :filled="bookmarked" />
                        </button>
                    </div>
                    <p v-if="likesCount > 0" class="text-xs font-bold text-gray-800 dark:text-white mb-2">
                        {{ likesCount.toLocaleString() }} {{ likesCount === 1 ? 'like' : 'likes' }}
                    </p>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-2.5" :title="fullDate(post.created_at)">{{ formatDate(post.created_at) }}</p>
                </div>

                <!-- Comment input -->
                <div class="border-t border-gray-100 dark:border-gray-800 px-4 py-3 flex items-center gap-2 flex-shrink-0">
                    <img :src="authUser.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                    <input ref="commentInput"
                           v-model="newComment"
                           placeholder="Add a comment…"
                           class="flex-1 text-sm outline-none bg-transparent text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
                           @keyup.enter="submitComment" />
                    <button @click="submitComment"
                            :disabled="!newComment.trim() || submitting"
                            class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 disabled:opacity-30 transition-all">
                        Post
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
