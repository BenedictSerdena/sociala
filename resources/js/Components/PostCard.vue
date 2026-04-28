<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';
import ImageLightbox from '@/Components/ImageLightbox.vue';
import { useToast } from '@/composables/useToast.js';
import PostContent from '@/Components/PostContent.vue';

const { show: toast } = useToast();

const props = defineProps({ post: Object });
const page = usePage();
const authUser = page.props.auth.user;

const liked = ref(props.post.is_liked);
const likesCount = ref(props.post.likes_count);
const showComments = ref(false);
const comments = ref([]);
const commentsLoaded = ref(false);
const commentsLoading = ref(false);
const commentForm = useForm({ content: '' });
const likeAnimating = ref(false);
const bookmarked = ref(props.post.is_bookmarked ?? false);

const editing = ref(false);
const editContent = ref(props.post.content);
const editSaving = ref(false);
const lightboxOpen = ref(false);

function formatDate(date) {
    const d = new Date(date);
    const diff = Math.floor((new Date() - d) / 1000);
    if (diff < 60) return `${diff}s`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function fullDate(date) {
    return new Date(date).toLocaleString('en-US', {
        month: 'long', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit',
    });
}

async function loadComments() {
    if (commentsLoaded.value) return;
    commentsLoading.value = true;
    const res = await axios.get(route('comments.index', props.post.id));
    comments.value = res.data;
    commentsLoaded.value = true;
    commentsLoading.value = false;
}

function toggleComments() {
    showComments.value = !showComments.value;
    if (showComments.value) loadComments();
}

async function toggleBookmark() {
    const res = await axios.post(route('bookmarks.toggle', props.post.id));
    bookmarked.value = res.data.bookmarked;
    toast(bookmarked.value ? 'Post saved' : 'Removed from saved', 'success');
}

async function saveEdit() {
    if (!editContent.value.trim() || editSaving.value) return;
    editSaving.value = true;
    const res = await axios.put(route('posts.update', props.post.id), { content: editContent.value });
    props.post.content = res.data.content;
    editing.value = false;
    editSaving.value = false;
    toast('Post updated', 'success');
}

async function toggleLike() {
    likeAnimating.value = true;
    setTimeout(() => likeAnimating.value = false, 350);
    const res = await axios.post(route('likes.toggle', props.post.id));
    liked.value = res.data.liked;
    likesCount.value = res.data.count;
}

function submitComment() {
    if (!commentForm.content.trim()) return;
    commentForm.post(route('comments.store', props.post.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            commentsLoaded.value = false;
            loadComments();
            props.post.comments_count++;
        },
    });
}

function deleteComment(commentId) {
    if (!confirm('Delete comment?')) return;
    useForm({}).delete(route('comments.destroy', commentId), {
        preserveScroll: true,
        onSuccess: () => {
            commentsLoaded.value = false;
            loadComments();
            props.post.comments_count--;
        },
    });
}

function deletePost() {
    if (!confirm('Delete this post?')) return;
    useForm({}).delete(route('posts.destroy', props.post.id), {
        preserveScroll: true,
        onSuccess: () => toast('Post deleted', 'success'),
    });
}

async function sharePost() {
    const url = route('posts.show', props.post.id);
    try {
        if (navigator.share) {
            await navigator.share({ url, title: `Post by ${props.post.user.name}` });
        } else {
            await navigator.clipboard.writeText(url);
            toast('Link copied!', 'success');
        }
    } catch {}
}
</script>

<template>
    <article class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">

        <!-- ── Header ── -->
        <div class="flex items-center justify-between px-4 py-3">
            <Link :href="route('profile.show', { user: post.user.username })" class="flex items-center gap-3 group">
                <div class="relative">
                    <img :src="post.user.avatar_url || `https://ui-avatars.com/api/?name=${post.user.name}&background=6366f1&color=fff`"
                         class="w-10 h-10 rounded-full object-cover ring-2 ring-transparent group-hover:ring-indigo-200 transition-all" />
                </div>
                <div>
                    <p class="font-bold text-gray-900 dark:text-white text-sm leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ post.user.name }}</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs font-medium">
                        @{{ post.user.username }}
                        <span class="mx-1">·</span>
                        <time :title="fullDate(post.created_at)">{{ formatDate(post.created_at) }}</time>
                    </p>
                </div>
            </Link>

            <div v-if="post.user.id === authUser.id" class="flex items-center gap-1">
                <button @click="editing = !editing"
                        class="p-1.5 rounded-lg text-gray-300 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all">
                    <GIcon name="Edit" :size="16" />
                </button>
                <button @click="deletePost"
                        class="p-1.5 rounded-lg text-gray-300 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition-all">
                    <GIcon name="Trash" :size="16" />
                </button>
            </div>
        </div>

        <!-- ── Caption / Edit mode ── -->
        <div class="px-4 pb-3">
            <div v-if="editing" class="space-y-2">
                <textarea v-model="editContent" rows="3" autofocus
                          class="w-full text-sm text-gray-800 dark:text-gray-100 border border-indigo-300 dark:border-indigo-700 rounded-xl px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-gray-50 dark:bg-gray-800"></textarea>
                <div class="flex gap-2 justify-end">
                    <button @click="editing = false; editContent = post.content"
                            class="px-3 py-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">Cancel</button>
                    <button @click="saveEdit" :disabled="editSaving || !editContent.trim()"
                            class="px-3 py-1 text-xs font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-all">
                        {{ editSaving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>
            <PostContent v-else :content="post.content" />
        </div>

        <!-- ── Image ── -->
        <div v-if="post.image_url" class="relative overflow-hidden">
            <img :src="post.image_url"
                 class="w-full object-cover max-h-[520px] cursor-zoom-in hover:brightness-95 transition-all"
                 @click="lightboxOpen = true" />
        </div>
        <ImageLightbox v-if="lightboxOpen && post.image_url"
                       :src="post.image_url"
                       @close="lightboxOpen = false" />

        <!-- ── Action Bar ── -->
        <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100/80 dark:border-gray-800">
            <div class="flex items-center gap-0.5">

                <!-- Like -->
                <button @click="toggleLike"
                        class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl transition-all text-sm font-semibold group"
                        :class="liked
                            ? 'text-red-500'
                            : 'text-gray-500 dark:text-gray-400 hover:text-red-400 hover:bg-red-50/60 dark:hover:bg-red-950/40'">
                    <span class="transition-transform duration-200" :class="likeAnimating ? 'scale-125' : 'scale-100'">
                        <GIcon name="Heart" :size="21" :filled="liked" />
                    </span>
                    <span v-if="likesCount > 0" class="text-xs tabular-nums">{{ likesCount.toLocaleString() }}</span>
                </button>

                <!-- Comment -->
                <button @click="toggleComments"
                        class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl text-sm font-semibold transition-all"
                        :class="showComments
                            ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/60 dark:bg-indigo-950/40'
                            : 'text-gray-500 dark:text-gray-400 hover:text-indigo-500 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40'">
                    <GIcon name="Annotation" :size="21" />
                    <span v-if="post.comments_count > 0" class="text-xs tabular-nums">{{ post.comments_count }}</span>
                </button>

                <!-- Share -->
                <button @click="sharePost"
                        class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl text-sm font-semibold transition-all text-gray-500 dark:text-gray-400 hover:text-sky-500 hover:bg-sky-50/60 dark:hover:bg-sky-950/40">
                    <GIcon name="Share" :size="21" />
                </button>
            </div>

            <!-- Bookmark -->
            <button @click="toggleBookmark"
                    class="p-2 rounded-xl transition-all"
                    :class="bookmarked
                        ? 'text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-400 dark:text-gray-500 hover:text-indigo-500 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40'">
                <GIcon name="Bookmark" :size="20" :filled="bookmarked" />
            </button>
        </div>

        <!-- ── Comments ── -->
        <div v-if="showComments" class="border-t border-gray-100/80 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/30 px-4 py-3 space-y-3">
            <div v-if="commentsLoading" class="text-center text-gray-400 dark:text-gray-500 text-xs py-1">
                Loading…
            </div>
            <div v-else-if="comments.length === 0" class="text-center text-gray-400 dark:text-gray-500 text-xs py-1">
                No comments yet — be the first!
            </div>

            <div v-for="comment in comments" :key="comment.id" class="flex gap-2.5 group/comment">
                <img :src="comment.user?.avatar_url || `https://ui-avatars.com/api/?name=${comment.user?.name}&background=6366f1&color=fff`"
                     class="w-7 h-7 rounded-full object-cover flex-shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3.5 py-2.5 border border-gray-100 dark:border-gray-700 shadow-sm inline-block max-w-full">
                        <span class="font-bold text-xs text-gray-800 dark:text-gray-100">{{ comment.user?.name }} </span>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ comment.content }}</span>
                    </div>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 ml-1">{{ formatDate(comment.created_at) }}</p>
                </div>
                <button v-if="comment.user?.id === authUser.id" @click="deleteComment(comment.id)"
                        class="opacity-0 group-hover/comment:opacity-100 p-1 text-gray-300 hover:text-red-400 transition-all self-start mt-1 rounded">
                    <GIcon name="Close" :size="12" />
                </button>
            </div>

            <!-- Comment input -->
            <form @submit.prevent="submitComment" class="flex items-center gap-2 pt-1">
                <img :src="authUser.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                <div class="flex-1 flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full px-4 py-2 shadow-sm focus-within:ring-2 focus-within:ring-indigo-300 dark:focus-within:ring-indigo-700 focus-within:border-transparent transition-all">
                    <input v-model="commentForm.content" placeholder="Add a comment…"
                           class="flex-1 text-sm outline-none bg-transparent text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                    <button type="submit" :disabled="commentForm.processing || !commentForm.content.trim()"
                            class="text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                        <GIcon name="Send" :size="16" />
                    </button>
                </div>
            </form>
        </div>

    </article>
</template>
