<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profileUser: Object,
    posts: Object,
    reports: Array,
    auditLogs: Array,
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}
function formatTime(date) {
    return new Date(date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

const modal = ref({ open: false, title: '', message: '', danger: false, action: null });
function openModal(title, message, danger, action) {
    modal.value = { open: true, title, message, danger, action };
}
function onConfirm() {
    modal.value.open = false;
    modal.value.action?.();
}

function ban() {
    const label = props.profileUser.banned_at ? 'Unban' : 'Ban';
    openModal(`${label} User`, `${label} @${props.profileUser.username}?`, false,
        () => useForm({}).post(route('admin.users.ban', props.profileUser.id), { preserveScroll: true })
    );
}
function promote() {
    const label = props.profileUser.is_admin ? 'Remove admin from' : 'Promote';
    openModal('Change Role', `${label} @${props.profileUser.username}?`, false,
        () => useForm({}).post(route('admin.users.promote', props.profileUser.id), { preserveScroll: true })
    );
}
function deleteUser() {
    openModal('Delete Account', `Permanently delete @${props.profileUser.username} and ALL their data? This cannot be undone.`, true,
        () => useForm({}).delete(route('admin.users.destroy', props.profileUser.id))
    );
}
function deletePost(postId) {
    openModal('Delete Post', 'This post will be permanently removed.', true,
        () => useForm({}).delete(route('admin.posts.destroy', postId), { preserveScroll: true })
    );
}

const actionLabels = {
    'user.banned':               { label: 'Banned',          color: 'bg-red-100 text-red-700' },
    'user.unbanned':             { label: 'Unbanned',        color: 'bg-green-100 text-green-700' },
    'user.promoted':             { label: 'Promoted admin',  color: 'bg-indigo-100 text-indigo-700' },
    'user.demoted':              { label: 'Removed admin',   color: 'bg-slate-100 text-slate-600' },
};

const visibilityBadge = {
    public:   'bg-green-100 text-green-700',
    private:  'bg-slate-100 text-slate-600',
    archived: 'bg-amber-100 text-amber-700',
};

const reasonBadge = {
    spam:           'bg-yellow-100 text-yellow-700',
    harassment:     'bg-red-100 text-red-700',
    inappropriate:  'bg-orange-100 text-orange-700',
    misinformation: 'bg-purple-100 text-purple-700',
};
</script>

<template>
    <AdminLayout>
        <!-- Back -->
        <div class="mb-5">
            <Link :href="route('admin.users.index')" class="text-sm text-slate-500 hover:text-slate-800 flex items-center gap-1.5 font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Users
            </Link>
        </div>

        <!-- User profile card -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <img :src="profileUser.avatar_url" class="w-16 h-16 rounded-2xl object-cover" />
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-black text-slate-800">{{ profileUser.name }}</h1>
                            <span v-if="profileUser.is_admin" class="px-2 py-0.5 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-700">Admin</span>
                            <span v-if="profileUser.banned_at" class="px-2 py-0.5 rounded-lg text-xs font-bold bg-red-100 text-red-700">Banned</span>
                        </div>
                        <p class="text-slate-500 text-sm">@{{ profileUser.username }} · {{ profileUser.email }}</p>
                        <p class="text-slate-400 text-xs mt-1">Joined {{ formatDate(profileUser.created_at) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button @click="ban"
                            class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors"
                            :class="profileUser.banned_at ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
                        {{ profileUser.banned_at ? 'Unban User' : 'Ban User' }}
                    </button>
                    <button @click="promote"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors">
                        {{ profileUser.is_admin ? 'Remove Admin' : 'Make Admin' }}
                    </button>
                    <button @click="deleteUser"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                        Delete Account
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-100">
                <div class="text-center">
                    <p class="text-2xl font-black text-slate-800">{{ profileUser.posts_count }}</p>
                    <p class="text-xs text-slate-400 font-medium">Posts</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-black text-slate-800">{{ profileUser.followers_count }}</p>
                    <p class="text-xs text-slate-400 font-medium">Followers</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-black text-slate-800">{{ profileUser.following_count }}</p>
                    <p class="text-xs text-slate-400 font-medium">Following</p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
            <!-- Posts -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800">Posts ({{ posts.total }})</h2>
                </div>
                <div v-if="posts.data.length === 0" class="p-8 text-center text-slate-400 text-sm">No posts yet.</div>
                <div v-for="p in posts.data" :key="p.id" class="flex items-start gap-3 px-5 py-3.5 border-b border-slate-50 last:border-0 group">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-700 line-clamp-2">{{ p.content }}</p>
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-400">
                            <span>❤️ {{ p.likes_count }}</span>
                            <span>💬 {{ p.comments_count }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="visibilityBadge[p.visibility]">{{ p.visibility }}</span>
                            <span>{{ formatDate(p.created_at) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                        <Link :href="route('posts.show', p.id)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200">View</Link>
                        <button @click="deletePost(p.id)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200">Delete</button>
                    </div>
                </div>
                <div v-if="posts.prev_page_url || posts.next_page_url" class="flex gap-2 justify-center p-4 border-t border-slate-100">
                    <Link v-if="posts.prev_page_url" :href="posts.prev_page_url" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200">← Prev</Link>
                    <Link v-if="posts.next_page_url" :href="posts.next_page_url" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200">Next →</Link>
                </div>
            </div>

            <!-- Side: Reports + Audit -->
            <div class="space-y-4">
                <!-- Reports against this user -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="font-bold text-slate-800 text-sm">Reports Against Them</h2>
                    </div>
                    <div v-if="reports.length === 0" class="p-6 text-center text-slate-400 text-sm">No reports 🎉</div>
                    <div v-for="r in reports" :key="r.id" class="px-5 py-3 border-b border-slate-50 last:border-0">
                        <p class="text-xs text-slate-700 line-clamp-2">"{{ r.comment.content }}"</p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[11px] text-slate-400">{{ formatDate(r.created_at) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold" :class="reasonBadge[r.reason] ?? 'bg-slate-100 text-slate-600'">{{ r.reason }}</span>
                        </div>
                        <span v-if="r.resolved_at" class="text-[11px] text-green-600 font-semibold">Resolved</span>
                    </div>
                </div>

                <!-- Audit history for this user -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="font-bold text-slate-800 text-sm">Action History</h2>
                    </div>
                    <div v-if="auditLogs.length === 0" class="p-6 text-center text-slate-400 text-sm">No admin actions yet</div>
                    <div v-for="l in auditLogs" :key="l.id" class="px-5 py-3 border-b border-slate-50 last:border-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold"
                                  :class="(actionLabels[l.action] || { color: 'bg-slate-100 text-slate-600' }).color">
                                {{ (actionLabels[l.action] || { label: l.action }).label }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">by {{ l.admin.name }} · {{ formatTime(l.created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal
            :open="modal.open"
            :title="modal.title"
            :message="modal.message"
            :danger="modal.danger"
            confirm-text="Confirm"
            @confirm="onConfirm"
            @cancel="modal.open = false"
        />
    </AdminLayout>
</template>
