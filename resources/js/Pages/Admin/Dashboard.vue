<script setup>
import { onMounted, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Chart, LineElement, PointElement, LineController, CategoryScale, LinearScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineElement, PointElement, LineController, CategoryScale, LinearScale, Filler, Tooltip);

const props = defineProps({
    stats: Object,
    chartLabels: Array,
    signupsData: Array,
    postsData: Array,
    recentReports: Array,
    recentUsers: Array,
    recentAuditLogs: Array,
});

const signupsCanvas = ref(null);
const postsCanvas   = ref(null);

onMounted(() => {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { tooltip: { mode: 'index', intersect: false } },
        scales: {
            x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#94a3b8', font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 } },
        },
    };

    new Chart(signupsCanvas.value, {
        type: 'line',
        data: {
            labels: props.chartLabels,
            datasets: [{
                label: 'New Users',
                data: props.signupsData,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                borderWidth: 2,
            }],
        },
        options: baseOptions,
    });

    new Chart(postsCanvas.value, {
        type: 'line',
        data: {
            labels: props.chartLabels,
            datasets: [{
                label: 'New Posts',
                data: props.postsData,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14,165,233,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                borderWidth: 2,
            }],
        },
        options: baseOptions,
    });
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function formatTime(date) {
    return new Date(date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

const actionLabels = {
    'user.banned':              { label: 'Banned user',       color: 'bg-red-100 text-red-700' },
    'user.unbanned':            { label: 'Unbanned user',     color: 'bg-green-100 text-green-700' },
    'user.deleted':             { label: 'Deleted user',      color: 'bg-red-100 text-red-700' },
    'user.promoted':            { label: 'Promoted to admin', color: 'bg-indigo-100 text-indigo-700' },
    'user.demoted':             { label: 'Removed admin',     color: 'bg-slate-100 text-slate-600' },
    'report.dismissed':         { label: 'Dismissed report',  color: 'bg-slate-100 text-slate-600' },
    'comment.deleted_via_report':{ label: 'Deleted comment',  color: 'bg-orange-100 text-orange-700' },
    'post.deleted':             { label: 'Deleted post',      color: 'bg-orange-100 text-orange-700' },
};

const reasonLabels = { spam: 'Spam', harassment: 'Harassment', inappropriate: 'Inappropriate', misinformation: 'Misinformation' };
</script>

<template>
    <AdminLayout>
        <!-- Alert banner for pending reports -->
        <div v-if="stats.pending_reports >= 5"
             class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl px-5 py-3.5">
            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-red-800">{{ stats.pending_reports }} pending reports need attention</p>
                <p class="text-xs text-red-600 mt-0.5">Unresolved user reports are piling up. Review them soon.</p>
            </div>
            <Link :href="route('admin.reports.index')" class="flex-shrink-0 px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-colors">
                Review Now
            </Link>
        </div>

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
            <p class="text-slate-500 text-sm mt-1">Platform overview · last updated just now</p>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Total Users</p>
                <p class="text-3xl font-black text-slate-800">{{ stats.total_users.toLocaleString() }}</p>
                <p class="text-xs text-indigo-600 font-semibold mt-1">+{{ stats.new_users_week }} this week</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Total Posts</p>
                <p class="text-3xl font-black text-slate-800">{{ stats.total_posts.toLocaleString() }}</p>
                <p class="text-xs text-sky-600 font-semibold mt-1">+{{ stats.new_posts_week }} this week</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Pending Reports</p>
                <p class="text-3xl font-black" :class="stats.pending_reports > 0 ? 'text-amber-600' : 'text-slate-800'">
                    {{ stats.pending_reports }}
                </p>
                <p class="text-xs text-slate-400 font-medium mt-1">Need review</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Banned Users</p>
                <p class="text-3xl font-black text-slate-800">{{ stats.banned_users }}</p>
                <p class="text-xs text-slate-400 font-medium mt-1">Active bans</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4">New Users — last 30 days</p>
                <div class="h-48">
                    <canvas ref="signupsCanvas"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4">New Posts — last 30 days</p>
                <div class="h-48">
                    <canvas ref="postsCanvas"></canvas>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
            <!-- Pending reports -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-800 text-sm">Pending Reports</h2>
                    <Link :href="route('admin.reports.index')" class="text-xs text-indigo-600 font-semibold hover:underline">View all</Link>
                </div>
                <div v-if="recentReports.length === 0" class="p-8 text-center text-slate-400 text-sm">No pending reports 🎉</div>
                <div v-for="r in recentReports" :key="r.id" class="px-5 py-3 border-b border-slate-50 last:border-0">
                    <p class="text-xs text-slate-700 line-clamp-2">"{{ r.comment.content }}"</p>
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-xs text-slate-400">@{{ r.comment.user.name }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold">{{ reasonLabels[r.reason] ?? r.reason }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent users -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-800 text-sm">Recent Users</h2>
                    <Link :href="route('admin.users.index')" class="text-xs text-indigo-600 font-semibold hover:underline">View all</Link>
                </div>
                <div v-for="u in recentUsers" :key="u.id" class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 last:border-0">
                    <img :src="u.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <Link :href="route('admin.users.show', u.id)" class="text-xs font-semibold text-slate-800 hover:text-indigo-600 truncate">{{ u.name }}</Link>
                            <span v-if="u.is_admin" class="px-1 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">Admin</span>
                            <span v-if="u.banned_at" class="px-1 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">Banned</span>
                        </div>
                        <p class="text-[11px] text-slate-400">{{ formatDate(u.created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Audit log -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm">Audit Log</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Recent admin actions</p>
                </div>
                <div v-if="recentAuditLogs.length === 0" class="p-8 text-center text-slate-400 text-sm">No actions yet</div>
                <div v-for="l in recentAuditLogs" :key="l.id" class="px-5 py-3 border-b border-slate-50 last:border-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0"
                              :class="(actionLabels[l.action] || { color: 'bg-slate-100 text-slate-600' }).color">
                            {{ (actionLabels[l.action] || { label: l.action }).label }}
                        </span>
                        <span class="text-[11px] text-slate-400 flex-shrink-0">{{ formatTime(l.created_at) }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        by <span class="font-semibold">{{ l.admin.name }}</span>
                        <span v-if="l.meta?.username"> · @{{ l.meta.username }}</span>
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
