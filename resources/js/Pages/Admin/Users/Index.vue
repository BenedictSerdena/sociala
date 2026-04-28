<script setup>
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { router, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ users: Object, query: String });

const search = ref(props.query ?? '');
const selected = ref([]);
const bulkAction = ref('ban');

let debounce = null;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('admin.users.index'), { q: val }, { preserveState: true, replace: true });
        selected.value = [];
    }, 400);
});

const allSelected = computed(() =>
    props.users.data.length > 0 && props.users.data.every(u => selected.value.includes(u.id))
);

function toggleAll() {
    if (allSelected.value) selected.value = [];
    else selected.value = props.users.data.map(u => u.id);
}

function toggleOne(id) {
    const idx = selected.value.indexOf(id);
    if (idx === -1) selected.value.push(id);
    else selected.value.splice(idx, 1);
}

// ── Confirm modal state ──────────────────────────────────
const modal = ref({ open: false, title: '', message: '', danger: false, action: null });

function openModal(title, message, danger, action) {
    modal.value = { open: true, title, message, danger, action };
}
function onConfirm() {
    modal.value.open = false;
    modal.value.action?.();
}

function applyBulk() {
    if (!selected.value.length) return;
    const count = selected.value.length;
    openModal(
        'Apply Bulk Action',
        `Apply "${bulkAction.value}" to ${count} selected user(s)?`,
        bulkAction.value === 'delete',
        () => useForm({ ids: selected.value, action: bulkAction.value })
            .post(route('admin.users.bulk'), { preserveScroll: true, onSuccess: () => { selected.value = []; } })
    );
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function ban(user) {
    const label = user.banned_at ? 'Unban' : 'Ban';
    openModal(`${label} User`, `${label} @${user.username}?`, false,
        () => useForm({}).post(route('admin.users.ban', user.id), { preserveScroll: true })
    );
}

function promote(user) {
    const label = user.is_admin ? 'Remove admin role from' : 'Promote';
    openModal('Change Role', `${label} @${user.username}?`, false,
        () => useForm({}).post(route('admin.users.promote', user.id), { preserveScroll: true })
    );
}

function deleteUser(user) {
    openModal('Delete Account', `Permanently delete @${user.username} and all their data?`, true,
        () => useForm({}).delete(route('admin.users.destroy', user.id), { preserveScroll: true })
    );
}
</script>

<template>
    <AdminLayout>
        <div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Users</h1>
                <p class="text-slate-500 text-sm mt-1">{{ users.total }} total users</p>
            </div>
            <div class="flex items-center gap-2 bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-2 w-64">
                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="search" type="text" placeholder="Search users…" class="flex-1 text-sm outline-none bg-transparent text-slate-700" />
            </div>
        </div>

        <!-- Bulk action bar -->
        <div v-if="selected.length > 0"
             class="mb-4 flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-2xl px-5 py-3">
            <p class="text-sm font-semibold text-indigo-800 flex-1">{{ selected.length }} user(s) selected</p>
            <select v-model="bulkAction"
                    class="text-sm border border-indigo-300 rounded-lg px-3 py-1.5 bg-white text-slate-700 outline-none">
                <option value="ban">Ban</option>
                <option value="unban">Unban</option>
                <option value="delete">Delete</option>
            </select>
            <button @click="applyBulk"
                    class="px-4 py-1.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-colors">
                Apply
            </button>
            <button @click="selected = []" class="text-indigo-500 hover:text-indigo-800 text-sm font-medium">Cancel</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3 w-10">
                            <input type="checkbox" :checked="allSelected" @change="toggleAll"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        </th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">User</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">Posts</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden lg:table-cell">Followers</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden xl:table-cell">Joined</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="u in users.data" :key="u.id"
                        class="hover:bg-slate-50 transition-colors group"
                        :class="selected.includes(u.id) ? 'bg-indigo-50/60' : ''">
                        <td class="px-5 py-3.5">
                            <input type="checkbox" :checked="selected.includes(u.id)" @change="toggleOne(u.id)"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        </td>
                        <td class="px-4 py-3.5">
                            <Link :href="route('admin.users.show', u.id)" class="flex items-center gap-3 group/link">
                                <img :src="u.avatar_url" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-semibold text-slate-800 truncate group-hover/link:text-indigo-600 transition-colors">{{ u.name }}</p>
                                        <span v-if="u.is_admin" class="px-1.5 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700">Admin</span>
                                    </div>
                                    <p class="text-xs text-slate-400 truncate">@{{ u.username }} · {{ u.email }}</p>
                                </div>
                            </Link>
                        </td>
                        <td class="px-4 py-3.5 text-slate-600 hidden md:table-cell">{{ u.posts_count }}</td>
                        <td class="px-4 py-3.5 text-slate-600 hidden lg:table-cell">{{ u.followers_count }}</td>
                        <td class="px-4 py-3.5 text-slate-400 text-xs hidden xl:table-cell">{{ formatDate(u.created_at) }}</td>
                        <td class="px-4 py-3.5">
                            <span v-if="u.banned_at" class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Banned</span>
                            <span v-else class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="ban(u)"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                                        :class="u.banned_at ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'">
                                    {{ u.banned_at ? 'Unban' : 'Ban' }}
                                </button>
                                <button @click="promote(u)"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                                    {{ u.is_admin ? 'Demote' : 'Make Admin' }}
                                </button>
                                <button @click="deleteUser(u)"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="users.prev_page_url || users.next_page_url" class="flex gap-2 justify-center p-4 border-t border-slate-100">
                <Link v-if="users.prev_page_url" :href="users.prev_page_url"
                      class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200 transition-colors">← Prev</Link>
                <Link v-if="users.next_page_url" :href="users.next_page_url"
                      class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200 transition-colors">Next →</Link>
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
