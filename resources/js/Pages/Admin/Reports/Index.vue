<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useForm, Link } from '@inertiajs/vue3';

defineProps({ reports: Object });

const modal = ref({ open: false, title: '', message: '', danger: false, action: null });
function openModal(title, message, danger, action) {
    modal.value = { open: true, title, message, danger, action };
}
function onConfirm() {
    modal.value.open = false;
    modal.value.action?.();
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function dismiss(report) {
    openModal('Dismiss Report', 'Keep the comment and mark this report as resolved?', false,
        () => useForm({}).post(route('admin.reports.dismiss', report.id), { preserveScroll: true })
    );
}

function deleteComment(report) {
    openModal('Delete Comment', 'Delete this comment and resolve all related reports?', true,
        () => useForm({}).delete(route('admin.reports.deleteComment', report.id), { preserveScroll: true })
    );
}

const reasonBadge = {
    spam:          'bg-yellow-100 text-yellow-700',
    harassment:    'bg-red-100 text-red-700',
    inappropriate: 'bg-orange-100 text-orange-700',
    misinformation:'bg-purple-100 text-purple-700',
};
</script>

<template>
    <AdminLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Reported Comments</h1>
            <p class="text-slate-500 text-sm mt-1">{{ reports.total }} total reports</p>
        </div>

        <div class="space-y-3">
            <div v-if="reports.data.length === 0" class="bg-white rounded-2xl shadow-sm p-12 text-center text-slate-400">
                No reports to review 🎉
            </div>

            <div v-for="r in reports.data" :key="r.id"
                 class="bg-white rounded-2xl shadow-sm p-5 border-l-4 transition-colors"
                 :class="r.resolved_at ? 'border-green-300 opacity-60' : 'border-amber-400'">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                  :class="reasonBadge[r.reason] ?? 'bg-slate-100 text-slate-600'">
                                {{ r.reason }}
                            </span>
                            <span v-if="r.resolved_at" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Resolved</span>
                            <span class="text-xs text-slate-400">{{ formatDate(r.created_at) }}</span>
                        </div>

                        <blockquote class="bg-slate-50 rounded-xl px-4 py-3 text-sm text-slate-700 border border-slate-100 mb-3">
                            "{{ r.comment.content }}"
                            <footer class="text-xs text-slate-400 mt-1">— @{{ r.comment.user.username }}</footer>
                        </blockquote>

                        <p class="text-xs text-slate-500">
                            Reported by <span class="font-semibold text-slate-700">@{{ r.reporter.username }}</span>
                            ·
                            <Link :href="route('posts.show', r.comment.post_id)" class="text-indigo-600 hover:underline">View post</Link>
                        </p>
                    </div>

                    <div v-if="!r.resolved_at" class="flex flex-col gap-2 flex-shrink-0">
                        <button @click="dismiss(r)"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                            Dismiss
                        </button>
                        <button @click="deleteComment(r)"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                            Delete Comment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="reports.prev_page_url || reports.next_page_url" class="flex gap-2 justify-center mt-6">
            <Link v-if="reports.prev_page_url" :href="reports.prev_page_url"
                  class="px-4 py-2 bg-white shadow-sm text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 transition-colors">← Prev</Link>
            <Link v-if="reports.next_page_url" :href="reports.next_page_url"
                  class="px-4 py-2 bg-white shadow-sm text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 transition-colors">Next →</Link>
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
