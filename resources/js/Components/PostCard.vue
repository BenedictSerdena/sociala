<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';
import ImageLightbox from '@/Components/ImageLightbox.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useToast } from '@/composables/useToast.js';
import PostContent from '@/Components/PostContent.vue';

const { show: toast } = useToast();
const props = defineProps({ post: Object, autoOpenComments: { type: Boolean, default: false } });
const page = usePage();
const authUser = page.props.auth.user;

const liked        = ref(props.post.is_liked);
const likesCount   = ref(props.post.likes_count);
const bookmarked   = ref(props.post.is_bookmarked ?? false);
const likeAnimating = ref(false);

const showComments    = ref(false);
const comments        = ref([]);
const commentsLoaded  = ref(false);
const commentsLoading = ref(false);

const newComment  = ref('');
const submitting  = ref(false);

const replyingTo      = ref(null);
const replyContent    = ref('');
const replyInputRef   = ref(null);
const replySubmitting = ref(false);

const commentMenu   = ref(null); // { comment, parentComment|null }
const reportModal   = ref(null);
const reportReason  = ref('inappropriate');
const reportSending = ref(false);

const editing     = ref(false);
const editContent = ref(props.post.content);
const editSaving  = ref(false);
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
    return new Date(date).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}
function canDelete(comment) {
    return comment.user?.id === authUser.id || props.post.user.id === authUser.id;
}

async function loadComments() {
    if (commentsLoaded.value) return;
    commentsLoading.value = true;
    try {
        const res = await axios.get(route('comments.index', props.post.id));
        comments.value = res.data;
        commentsLoaded.value = true;
    } finally { commentsLoading.value = false; }
}
function toggleComments() {
    showComments.value = !showComments.value;
    if (showComments.value) loadComments();
}

onMounted(() => {
    if (props.autoOpenComments) {
        showComments.value = true;
        loadComments();
    }
});

async function submitComment() {
    if (!newComment.value.trim() || submitting.value) return;
    submitting.value = true;
    try {
        const res = await axios.post(route('comments.store', props.post.id), { content: newComment.value });
        comments.value.unshift(res.data);
        props.post.comments_count++;
        newComment.value = '';
        commentsLoaded.value = true;
    } finally { submitting.value = false; }
}
function startReply(comment) {
    replyingTo.value = comment.id;
    replyContent.value = '';
    nextTick(() => replyInputRef.value?.focus());
}
function cancelReply() { replyingTo.value = null; replyContent.value = ''; }
async function submitReply(parentComment) {
    if (!replyContent.value.trim() || replySubmitting.value) return;
    replySubmitting.value = true;
    try {
        const res = await axios.post(route('comments.store', props.post.id), { content: replyContent.value, parent_id: parentComment.id });
        if (!parentComment.replies) parentComment.replies = [];
        parentComment.replies.push(res.data);
        props.post.comments_count++;
        replyingTo.value = null;
        replyContent.value = '';
    } finally { replySubmitting.value = false; }
}
async function deleteComment(commentId, parentComment = null) {
    try {
        await axios.delete(route('comments.destroy', commentId));
        if (parentComment) parentComment.replies = parentComment.replies.filter(r => r.id !== commentId);
        else comments.value = comments.value.filter(c => c.id !== commentId);
        props.post.comments_count--;
        commentMenu.value = null;
        toast('Comment deleted', 'success');
    } catch { toast('Could not delete', 'error'); }
}
async function pinComment(comment) {
    const res = await axios.post(route('comments.pin', comment.id));
    comment.pinned_at = res.data.pinned ? new Date().toISOString() : null;
    commentMenu.value = null;
    toast(res.data.pinned ? 'Comment pinned' : 'Comment unpinned', 'success');
}
async function hideComment(comment) {
    const res = await axios.post(route('comments.hide', comment.id));
    comment.hidden_at = res.data.hidden ? new Date().toISOString() : null;
    commentMenu.value = null;
    toast(res.data.hidden ? 'Comment hidden' : 'Comment visible again', 'success');
}
function openCommentMenu(comment, parentComment = null) {
    commentMenu.value = commentMenu.value?.comment?.id === comment.id ? null : { comment, parentComment };
}
async function sendReport() {
    if (!reportModal.value || reportSending.value) return;
    reportSending.value = true;
    try {
        await axios.post(route('comments.report', reportModal.value.id), { reason: reportReason.value });
        toast('Comment reported — our team will review it', 'success');
        reportModal.value = null;
    } catch { toast('Already reported', 'error'); }
    finally { reportSending.value = false; }
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
    editing.value = false; editSaving.value = false;
    toast('Post updated', 'success');
}
async function toggleLike() {
    likeAnimating.value = true;
    setTimeout(() => likeAnimating.value = false, 350);
    const res = await axios.post(route('likes.toggle', props.post.id));
    liked.value = res.data.liked; likesCount.value = res.data.count;
}
const deleteConfirm = ref(false);
function deletePost() { deleteConfirm.value = true; }
function confirmDelete() {
    deleteConfirm.value = false;
    useForm({}).delete(route('posts.destroy', props.post.id), { preserveScroll: true, onSuccess: () => toast('Post deleted', 'success') });
}
async function sharePost() {
    const url = route('posts.show', props.post.id);
    try {
        if (navigator.share) await navigator.share({ url, title: `Post by ${props.post.user.name}` });
        else { await navigator.clipboard.writeText(url); toast('Link copied!', 'success'); }
    } catch {}
}
</script>

<template>
    <article class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3">
            <Link :href="route('profile.show', { user: post.user.username })" class="flex items-center gap-3 group">
                <img :src="post.user.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-transparent group-hover:ring-indigo-200 transition-all" />
                <div>
                    <p class="font-bold text-gray-900 dark:text-white text-sm leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ post.user.name }}</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs">@{{ post.user.username }} <span class="mx-0.5">·</span> <time :title="fullDate(post.created_at)">{{ formatDate(post.created_at) }}</time></p>
                </div>
            </Link>
            <div v-if="post.user.id === authUser.id" class="flex items-center gap-1">
                <button @click="editing = !editing" class="p-1.5 rounded-lg text-gray-300 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all"><GIcon name="Edit" :size="16" /></button>
                <button @click="deletePost" class="p-1.5 rounded-lg text-gray-300 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition-all"><GIcon name="Trash" :size="16" /></button>
                <ConfirmModal :open="deleteConfirm" title="Delete Post" message="This post will be permanently removed." confirm-text="Delete" :danger="true" @confirm="confirmDelete" @cancel="deleteConfirm = false" />
            </div>
        </div>

        <!-- Caption -->
        <div class="px-4 pb-3">
            <div v-if="editing" class="space-y-2">
                <textarea v-model="editContent" rows="3" autofocus class="w-full text-sm text-gray-800 dark:text-gray-100 border border-indigo-300 dark:border-indigo-700 rounded-xl px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-gray-50 dark:bg-gray-800"></textarea>
                <div class="flex gap-2 justify-end">
                    <button @click="editing = false; editContent = post.content" class="px-3 py-1 text-xs text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">Cancel</button>
                    <button @click="saveEdit" :disabled="editSaving || !editContent.trim()" class="px-3 py-1 text-xs font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-all">{{ editSaving ? 'Saving…' : 'Save' }}</button>
                </div>
            </div>
            <PostContent v-else :content="post.content" />
        </div>

        <!-- Image -->
        <div v-if="post.image_url" class="overflow-hidden">
            <img :src="post.image_url" class="w-full object-cover max-h-[520px] cursor-zoom-in hover:brightness-95 transition-all" @click="lightboxOpen = true" />
        </div>
        <ImageLightbox v-if="lightboxOpen && post.image_url" :src="post.image_url" @close="lightboxOpen = false" />

        <!-- Action Bar -->
        <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100/80 dark:border-gray-800">
            <div class="flex items-center gap-0.5">
                <button @click="toggleLike" class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl transition-all text-sm font-semibold" :class="liked ? 'text-red-500' : 'text-gray-500 dark:text-gray-400 hover:text-red-400 hover:bg-red-50/60 dark:hover:bg-red-950/40'">
                    <span class="transition-transform duration-200" :class="likeAnimating ? 'scale-125' : 'scale-100'"><GIcon name="Heart" :size="21" :filled="liked" /></span>
                    <span v-if="likesCount > 0" class="text-xs tabular-nums">{{ likesCount.toLocaleString() }}</span>
                </button>
                <button @click="toggleComments" class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl text-sm font-semibold transition-all" :class="showComments ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/60 dark:bg-indigo-950/40' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-500 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40'">
                    <GIcon name="Annotation" :size="21" />
                    <span v-if="post.comments_count > 0" class="text-xs tabular-nums">{{ post.comments_count }}</span>
                </button>
                <button @click="sharePost" class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl text-sm font-semibold transition-all text-gray-500 dark:text-gray-400 hover:text-sky-500 hover:bg-sky-50/60 dark:hover:bg-sky-950/40">
                    <GIcon name="Share" :size="21" />
                </button>
            </div>
            <button @click="toggleBookmark" class="p-2 rounded-xl transition-all" :class="bookmarked ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 hover:text-indigo-500 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40'">
                <GIcon name="Bookmark" :size="20" :filled="bookmarked" />
            </button>
        </div>

        <!-- Comments Section -->
        <div v-if="showComments" class="border-t border-gray-100/80 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/20">
            <div v-if="commentsLoading" class="flex justify-center py-6">
                <div class="w-5 h-5 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else class="px-4 pt-3 pb-3 space-y-4">

                <!-- click-outside to close comment menu -->
                <div v-if="commentMenu" class="fixed inset-0 z-10" @click="commentMenu = null"></div>

                <p v-if="comments.length === 0" class="text-center text-gray-400 dark:text-gray-500 text-xs py-2">No comments yet — be the first!</p>

                <!-- Top-level comments -->
                <div v-for="comment in comments" :key="comment.id" class="space-y-2">

                    <!-- Hidden comment (post owner sees collapsed bar) -->
                    <div v-if="comment.hidden_at && post.user.id !== authUser.id"></div>
                    <div v-else-if="comment.hidden_at"
                         class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                        <span class="text-[11px] text-gray-400 flex-1">Comment hidden from others</span>
                        <button @click="hideComment(comment)" class="text-[10px] font-semibold text-indigo-500 hover:underline">Unhide</button>
                    </div>

                    <div v-else class="flex gap-2.5 group/c">
                        <Link :href="route('profile.show', { user: comment.user?.username })">
                            <img :src="comment.user?.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0 mt-0.5 hover:opacity-80 transition-opacity" />
                        </Link>
                        <div class="flex-1 min-w-0">
                            <!-- Pinned badge -->
                            <div v-if="comment.pinned_at" class="flex items-center gap-1 mb-1">
                                <svg class="w-3 h-3 text-indigo-500" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                <span class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wide">Pinned</span>
                            </div>

                            <!-- Bubble -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3.5 py-2.5 border border-gray-100 dark:border-gray-700 shadow-sm inline-block max-w-full"
                                 :class="comment.pinned_at ? 'border-indigo-200 dark:border-indigo-800' : ''">
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <Link :href="route('profile.show', { user: comment.user?.username })" class="font-bold text-xs text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors leading-snug">{{ comment.user?.name }}</Link>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 leading-snug">{{ comment.content }}</span>
                                </div>
                            </div>

                            <!-- Meta row -->
                            <div class="flex items-center gap-3 mt-1 ml-1">
                                <time class="text-[10px] text-gray-400 dark:text-gray-500" :title="fullDate(comment.created_at)">{{ formatDate(comment.created_at) }}</time>
                                <button @click="startReply(comment)" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Reply</button>
                                <!-- 3-dots menu trigger -->
                                <div class="relative ml-auto">
                                    <button @click.stop="openCommentMenu(comment)"
                                            class="p-1 rounded-lg text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                                        <GIcon name="MoreHorizontal" :size="15" />
                                    </button>
                                    <!-- Dropdown -->
                                    <div v-if="commentMenu?.comment?.id === comment.id"
                                         class="absolute right-0 bottom-full mb-1 w-44 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-20 py-1">
                                        <!-- Post owner actions -->
                                        <template v-if="post.user.id === authUser.id">
                                            <button @click="pinComment(comment)"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                                {{ comment.pinned_at ? 'Unpin comment' : 'Pin comment' }}
                                            </button>
                                            <button @click="hideComment(comment)"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                                Hide comment
                                            </button>
                                            <div class="h-px bg-gray-100 dark:bg-gray-700 mx-2"></div>
                                        </template>
                                        <!-- Delete (own comment OR post owner) -->
                                        <button v-if="canDelete(comment)"
                                                @click="deleteComment(comment.id)"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">
                                            <GIcon name="Trash" :size="14" class="flex-shrink-0" />
                                            Delete comment
                                        </button>
                                        <!-- Report (other users' comments) -->
                                        <button v-if="comment.user?.id !== authUser.id"
                                                @click="reportModal = comment; reportReason = 'inappropriate'; commentMenu = null"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-950/30 transition-colors">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                                            Report comment
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Reply input -->
                            <div v-if="replyingTo === comment.id" class="flex items-center gap-2 mt-2">
                                <img :src="authUser.avatar_url" class="w-6 h-6 rounded-full object-cover flex-shrink-0" />
                                <div class="flex-1 flex items-center gap-2 bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 rounded-full px-3 py-1.5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-300 dark:focus-within:ring-indigo-700 transition-all">
                                    <input ref="replyInputRef" v-model="replyContent" :placeholder="`Reply to ${comment.user?.name}…`" class="flex-1 text-xs outline-none bg-transparent text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" @keyup.enter="submitReply(comment)" @keyup.escape="cancelReply" />
                                    <button @click="cancelReply" class="text-gray-300 hover:text-gray-500 flex-shrink-0"><GIcon name="Close" :size="12" /></button>
                                    <button @click="submitReply(comment)" :disabled="!replyContent.trim() || replySubmitting" class="text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 disabled:opacity-30 transition-colors flex-shrink-0"><GIcon name="Send" :size="14" /></button>
                                </div>
                            </div>

                            <!-- Replies (nested) -->
                            <div v-if="comment.replies?.length" class="mt-2.5 pl-3 border-l-2 border-indigo-100 dark:border-indigo-900/50 space-y-2.5">
                                <div v-for="reply in comment.replies" :key="reply.id" class="flex gap-2 group/r">
                                    <Link :href="route('profile.show', { user: reply.user?.username })">
                                        <img :src="reply.user?.avatar_url" class="w-6 h-6 rounded-full object-cover flex-shrink-0 mt-0.5 hover:opacity-80 transition-opacity" />
                                    </Link>
                                    <div class="flex-1 min-w-0">
                                        <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2 border border-gray-100 dark:border-gray-700 shadow-sm inline-block max-w-full">
                                            <div class="flex flex-wrap items-baseline gap-1">
                                                <Link :href="route('profile.show', { user: reply.user?.username })" class="font-bold text-xs text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors leading-snug">{{ reply.user?.name }}</Link>
                                                <span class="text-xs text-gray-700 dark:text-gray-300 leading-snug">{{ reply.content }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1 ml-0.5">
                                            <time class="text-[10px] text-gray-400 dark:text-gray-500" :title="fullDate(reply.created_at)">{{ formatDate(reply.created_at) }}</time>
                                            <!-- Reply 3-dots -->
                                            <div class="relative ml-auto">
                                                <button @click.stop="openCommentMenu(reply, comment)"
                                                        class="p-1 rounded-lg text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                                                    <GIcon name="MoreHorizontal" :size="13" />
                                                </button>
                                                <div v-if="commentMenu?.comment?.id === reply.id"
                                                     class="absolute right-0 bottom-full mb-1 w-44 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-20 py-1">
                                                    <button v-if="canDelete(reply)"
                                                            @click="deleteComment(reply.id, comment)"
                                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">
                                                        <GIcon name="Trash" :size="14" class="flex-shrink-0" />
                                                        Delete reply
                                                    </button>
                                                    <button v-if="reply.user?.id !== authUser.id"
                                                            @click="reportModal = reply; reportReason = 'inappropriate'; commentMenu = null"
                                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-950/30 transition-colors">
                                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                                                        Report reply
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New comment input -->
                <div class="flex items-center gap-2 pt-1 border-t border-gray-100/60 dark:border-gray-700/50">
                    <img :src="authUser.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                    <div class="flex-1 flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full px-4 py-2 shadow-sm focus-within:ring-2 focus-within:ring-indigo-300 dark:focus-within:ring-indigo-700 focus-within:border-transparent transition-all">
                        <input v-model="newComment" placeholder="Add a comment…" class="flex-1 text-sm outline-none bg-transparent text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" @keyup.enter="submitComment" />
                        <button @click="submitComment" :disabled="submitting || !newComment.trim()" class="text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><GIcon name="Send" :size="16" /></button>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <!-- Report Modal -->
    <Teleport to="body">
        <div v-if="reportModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-4" @click.self="reportModal = null">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="reportModal = null"></div>
            <div class="relative bg-white dark:bg-gray-900 w-full sm:max-w-sm rounded-t-2xl sm:rounded-2xl shadow-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 dark:bg-orange-950 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm">Report Comment</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Help us understand the issue</p>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl px-3 py-2.5 mb-4 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic line-clamp-2">"{{ reportModal?.content }}"</p>
                </div>
                <div class="space-y-1.5 mb-5">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2">Reason</p>
                    <label v-for="r in [
                        { value: 'spam',           label: 'Spam or unwanted ads' },
                        { value: 'harassment',     label: 'Harassment or bullying' },
                        { value: 'inappropriate',  label: 'Inappropriate content' },
                        { value: 'misinformation', label: 'False information' },
                    ]" :key="r.value" class="flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer transition-colors" :class="reportReason === r.value ? 'bg-orange-50 dark:bg-orange-950 text-orange-600 dark:text-orange-400' : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300'">
                        <input type="radio" v-model="reportReason" :value="r.value" class="accent-orange-500" />
                        <span class="text-sm font-medium">{{ r.label }}</span>
                    </label>
                </div>
                <div class="flex gap-3">
                    <button @click="reportModal = null" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">Cancel</button>
                    <button @click="sendReport" :disabled="reportSending" class="flex-1 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold transition-all disabled:opacity-50">{{ reportSending ? 'Sending…' : 'Report' }}</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
