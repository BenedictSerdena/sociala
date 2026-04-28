<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentUser = page.props.auth.user;
const sidebarOpen = ref(false);
const pendingReports = computed(() => page.props.pendingReports ?? 0);

const navItems = [
    { label: 'Dashboard', href: route('admin.dashboard'), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { label: 'Users', href: route('admin.users.index'), icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { label: 'Reports', href: route('admin.reports.index'), icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', badge: true },
    { label: 'Posts', href: route('admin.posts.index'), icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z' },
];

function isActive(href) {
    return window.location.pathname === new URL(href).pathname;
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex">
        <!-- Sidebar -->
        <aside class="w-60 bg-slate-900 text-white flex flex-col flex-shrink-0 hidden lg:flex">
            <div class="px-6 py-5 border-b border-slate-700">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1">Admin Panel</p>
                <p class="text-white font-bold text-lg leading-tight">Control Center</p>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link v-for="item in navItems" :key="item.label"
                      :href="item.href"
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
                      :class="isActive(item.href)
                          ? 'bg-indigo-600 text-white shadow-md'
                          : 'text-slate-300 hover:bg-slate-800 hover:text-white'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    <span class="flex-1">{{ item.label }}</span>
                    <span v-if="item.badge && pendingReports > 0"
                          class="ml-auto min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center bg-red-500 text-white">
                        {{ pendingReports }}
                    </span>
                </Link>
            </nav>

            <div class="px-4 py-4 border-t border-slate-700">
                <Link :href="route('feed')" class="flex items-center gap-2 text-slate-400 hover:text-white text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to App
                </Link>
                <div class="flex items-center gap-2 mt-3">
                    <img :src="currentUser.avatar_url" class="w-8 h-8 rounded-full object-cover" />
                    <div class="min-w-0">
                        <p class="text-white text-xs font-semibold truncate">{{ currentUser.name }}</p>
                        <p class="text-slate-400 text-xs truncate">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile header -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-slate-900 text-white flex items-center justify-between px-4 py-3 shadow">
            <p class="font-bold text-sm">Admin Panel</p>
            <button @click="sidebarOpen = !sidebarOpen" class="p-1.5 rounded-lg hover:bg-slate-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Mobile drawer -->
        <Teleport to="body">
            <div v-if="sidebarOpen" class="lg:hidden fixed inset-0 z-50 flex">
                <div class="fixed inset-0 bg-black/60" @click="sidebarOpen = false" />
                <aside class="relative w-64 bg-slate-900 text-white flex flex-col h-full">
                    <div class="px-6 py-5 border-b border-slate-700">
                        <p class="text-white font-bold text-lg">Admin Panel</p>
                    </div>
                    <nav class="flex-1 px-3 py-4 space-y-1">
                        <Link v-for="item in navItems" :key="item.label"
                              :href="item.href" @click="sidebarOpen = false"
                              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
                              :class="isActive(item.href) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span class="flex-1">{{ item.label }}</span>
                            <span v-if="item.badge && pendingReports > 0"
                                  class="min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center bg-red-500 text-white">
                                {{ pendingReports }}
                            </span>
                        </Link>
                    </nav>
                    <div class="px-4 py-4 border-t border-slate-700">
                        <Link :href="route('feed')" @click="sidebarOpen = false" class="text-slate-400 hover:text-white text-sm">← Back to App</Link>
                    </div>
                </aside>
            </div>
        </Teleport>

        <!-- Main content -->
        <main class="flex-1 min-w-0 lg:overflow-y-auto">
            <div class="pt-14 lg:pt-0 p-6 max-w-7xl mx-auto">
                <slot />
            </div>
        </main>
    </div>
</template>
