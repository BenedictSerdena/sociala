<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    post: Object,
});

const page = usePage();
const authUser = page.props.auth.user;

const liked = ref(props.post.is_liked);
const likesCount = ref(props.post.likes_count);
const showComments = ref(false);
const comments = ref(props.post.comments?.data ?? props.post.comments ?? []);

const commentForm = useForm({ content: '' });

async function toggleLike() {
    const res = await axios.post(route('likes.toggle', props.post.id));
    liked.value = res.data.liked;
    likesCount.value = res.data.count;
}

function submitComment() {
    commentForm.post(route('comments.store', props.post.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
        },
    });
}

function deleteComment(commentId) {
    if (!confirm('Delete this comment?')) return;
    useForm({}).delete(route('comments.destroy', commentId), { preserveScroll: true });
}

function deletePost() {
    if (!confirm('Delete this post?')) return;
    useForm({}).delete(route('posts.destroy', props.post.id), { preserveScroll: true });
}
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 pt-4 pb-2">
            <Link :href="route('profile.show', { user: post.user.username })" class="flex items-center gap-3">
                <img :src="post.user.avatar_url || `https://ui-avatars.com/api/?name=${post.user.name}&background=6366f1&color=fff`"
                     class="w-10 h-10 rounded-full object-cover" />
                <div>
                    <p class="font-semibold text-gray-900 text-sm leading-tight">{{ post.user.name }}</p>
                    <p class="text-gray-400 text-xs">@{{ post.user.username }} · {{ new Date(post.created_at).toLocaleDateString() }}</p>
                </div>
            </Link>
            <button v-if="post.user.id === authUser.id" @click="deletePost"
                    class="text-gray-400 hover:text-red-500 text-xs">Delete</button>
        </div>

        <!-- Content -->
        <div class="px-4 pb-3">
            <p class="text-gray-800 text-sm whitespace-pre-line">{{ post.content }}</p>
        </div>

        <!-- Image -->
        <img v-if="post.image_url" :src="post.image_url" class="w-full object-cover max-h-96" />

        <!-- Actions -->
        <div class="flex items-center gap-6 px-4 py-3 border-t border-gray-50">
            <button @click="toggleLike" class="flex items-center gap-1.5 text-sm transition-colors"
                    :class="liked ? 'text-red-500' : 'text-gray-500 hover:text-red-400'">
                <svg class="w-5 h-5" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                {{ likesCount }}
            </button>
            <button @click="showComments = !showComments" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                {{ post.comments_count }}
            </button>
        </div>

        <!-- Comments Section -->
        <div v-if="showComments" class="border-t border-gray-50 px-4 py-3 space-y-3">
            <div v-for="comment in comments" :key="comment.id" class="flex gap-2">
                <img :src="comment.user?.avatar_url || `https://ui-avatars.com/api/?name=${comment.user?.name}&background=6366f1&color=fff`"
                     class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                <div class="bg-gray-50 rounded-xl px-3 py-2 flex-1">
                    <p class="font-semibold text-xs text-gray-800">{{ comment.user?.name }}</p>
                    <p class="text-sm text-gray-700">{{ comment.content }}</p>
                </div>
                <button v-if="comment.user?.id === authUser.id" @click="deleteComment(comment.id)"
                        class="text-gray-300 hover:text-red-400 text-xs self-start mt-1">✕</button>
            </div>

            <form @submit.prevent="submitComment" class="flex gap-2">
                <input v-model="commentForm.content" placeholder="Write a comment…"
                       class="flex-1 border border-gray-200 rounded-full px-4 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                <button type="submit" :disabled="commentForm.processing"
                        class="bg-indigo-600 text-white text-sm px-4 py-1.5 rounded-full hover:bg-indigo-700 disabled:opacity-50">
                    Post
                </button>
            </form>
        </div>
    </div>
</template>
