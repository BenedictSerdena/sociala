<script setup>
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { router, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ posts: Object, query: String });

const search = ref(props.query ?? '');
let debounce = null;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('admin.posts.index'), { q: val }, { preserveState: true, replace: true });
    }, 400);
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const modal = ref({ open: false, post: null });
function deletePost(post) { modal.value = { open: true, post }; }
function confirmDelete() {
    const post = modal.value.post;
    modal.value = { open: false, post: null };
    useForm({}).delete(route('admin.posts.destroy', post.id), { preserveScroll: true });
}

const visibilityBadge = {
    public:   'bg-green-100 text-green-700',
    private:  'bg-slate-100 text-slate-600',
    archived: 'bg-amber-100 text-amber-700',
};
</script>

<template>
    <AdminLayout>
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Posts</h1>
                <p class="text-slate-500 text-sm mt-1">{{ posts.total }} total posts</p>
            </div>
            <div class="flex items-center gap-2 bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-2 w-64">
                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="search" type="text" placeholder="Search content…" class="flex-1 text-sm outline-none bg-transparent text-slate-700" />
            </div>
        </div>

        <div class="space-y-3">
            <div v-if="posts.data.length === 0" class="bg-white rounded-2xl shadow-sm p-12 text-center text-slate-400">
                No posts found.
            </div>

            <div v-for="p in posts.data" :key="p.id"
                 class="bg-white rounded-2xl shadow-sm p-5 flex gap-4 group">
                <img :src="p.user.avatar_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-slate-800 text-sm">{{ p.user.name }}</p>
                            <span class="text-slate-400 text-xs">@{{ p.user.username }}</span>
                            <span class="text-slate-300 text-xs">·</span>
                            <span class="text-xs text-slate-400">{{ formatDate(p.created_at) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="visibilityBadge[p.visibility] ?? 'bg-slate-100 text-slate-600'">
                                {{ p.visibility }}
                            </span>
                            <button @click="deletePost(p)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100">
                                Delete
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-slate-700 line-clamp-3">{{ p.content }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span>❤️ {{ p.likes_count }}</span>
                        <span>💬 {{ p.comments_count }}</span>
                        <Link :href="route('posts.show', p.id)" class="text-indigo-600 hover:underline">View post</Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center mt-6">
            <Link v-if="posts.prev_page_url" :href="posts.prev_page_url"
                  class="px-4 py-2 bg-white shadow-sm text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 transition-colors">← Prev</Link>
            <Link v-if="posts.next_page_url" :href="posts.next_page_url"
                  class="px-4 py-2 bg-white shadow-sm text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 transition-colors">Next →</Link>
        </div>
        <ConfirmModal
            :open="modal.open"
            :title="`Delete Post by @${modal.post?.user?.username ?? ''}`"
            message="This post will be permanently removed."
            :danger="true"
            confirm-text="Delete"
            @confirm="confirmDelete"
            @cancel="modal.open = false"
        />
    </AdminLayout>
</template>
