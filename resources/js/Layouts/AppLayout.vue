<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const page = usePage();
const user = page.props.auth.user;
const unreadCount = ref(0);

onMounted(async () => {
    try {
        const res = await axios.get(route('notifications.unread'));
        unreadCount.value = res.data.count;
        window.Echo.private(`notifications.${user.id}`)
            .listen('NewNotification', () => { unreadCount.value++; });
    } catch {}
});

const isActive = (prefix) => page.url.startsWith(prefix);
const isProfile = () => page.url.includes(`/${user.username}`);
</script>

<template>
    <div class="min-h-screen bg-[#F5F5F7]">

        <!-- ─── Desktop Sidebar ─── -->
        <aside class="hidden lg:flex flex-col fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-200/80 z-40 px-4 py-6">

            <!-- Logo -->
            <Link :href="route('feed')" class="flex items-center gap-3 px-2 mb-8">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                    <GIcon name="Users" :size="20" class="text-white" />
                </div>
                <span class="text-xl font-black tracking-tight bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">SocialApp</span>
            </Link>

            <!-- Nav -->
            <nav class="flex flex-col gap-1 flex-1">

                <Link :href="route('feed')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/feed') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                    <GIcon name="Home" :size="20" :filled="isActive('/feed')" />
                    Home
                </Link>

                <Link :href="route('messages.index')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/messages') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                    <GIcon name="AnnotationDots" :size="20" :filled="isActive('/messages')" />
                    Messages
                </Link>

                <Link :href="route('notifications.index')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm relative"
                      :class="isActive('/notifications') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                    <div class="relative">
                        <GIcon name="Notification" :size="20" :filled="isActive('/notifications')" />
                        <span v-if="unreadCount > 0"
                              class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-bold min-w-[16px] h-4 px-0.5 rounded-full flex items-center justify-center">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </div>
                    Notifications
                </Link>

                <Link :href="route('profile.show', { user: user.username })"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isProfile() ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                    <img :src="user.avatar_url" class="w-5 h-5 rounded-full object-cover ring-1 ring-indigo-200" />
                    Profile
                </Link>

                <Link :href="route('profile.edit')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/profile/edit') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                    <GIcon name="Settings" :size="20" />
                    Settings
                </Link>

            </nav>

            <!-- User Footer -->
            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-center gap-3 px-2 mb-3">
                    <img :src="user.avatar_url" class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-100" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ user.name }}</p>
                        <p class="text-xs text-gray-400 truncate">@{{ user.username }}</p>
                    </div>
                </div>
                <Link :href="route('logout')" method="post" as="button"
                      class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-500 hover:bg-red-50 hover:text-red-500 transition-all w-full text-sm font-medium">
                    <GIcon name="LogOut" :size="18" />
                    Log out
                </Link>
            </div>
        </aside>

        <!-- ─── Mobile Top Bar ─── -->
        <header class="lg:hidden bg-white border-b border-gray-200/80 sticky top-0 z-40 shadow-sm">
            <div class="flex items-center justify-between px-4 h-14">
                <Link :href="route('feed')" class="text-xl font-black tracking-tight bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                    SocialApp
                </Link>
                <Link :href="route('notifications.index')" class="relative p-2 rounded-xl hover:bg-gray-100 transition-colors">
                    <GIcon name="Notification" :size="22" :filled="isActive('/notifications')" class="text-gray-700" />
                    <span v-if="unreadCount > 0"
                          class="absolute top-1 right-1 bg-red-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                </Link>
            </div>
        </header>

        <!-- ─── Main Content ─── -->
        <main class="lg:ml-64 min-h-screen">
            <div class="max-w-5xl mx-auto px-4 py-6 pb-24 lg:pb-8">
                <slot />
            </div>
        </main>

        <!-- ─── Mobile Bottom Nav ─── -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 z-40 safe-area-bottom shadow-lg">
            <div class="flex items-center justify-around h-14 px-2">

                <Link :href="route('feed')" class="flex flex-col items-center p-2 rounded-xl transition-colors"
                      :class="isActive('/feed') ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-700'">
                    <GIcon name="Home" :size="24" :filled="isActive('/feed')" />
                </Link>

                <Link :href="route('messages.index')" class="flex flex-col items-center p-2 rounded-xl transition-colors"
                      :class="isActive('/messages') ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-700'">
                    <GIcon name="AnnotationDots" :size="24" :filled="isActive('/messages')" />
                </Link>

                <Link :href="route('profile.show', { user: user.username })" class="flex flex-col items-center p-2"
                      :class="isProfile() ? 'text-indigo-600' : 'text-gray-400'">
                    <img :src="user.avatar_url" class="w-7 h-7 rounded-full object-cover" :class="isProfile() ? 'ring-2 ring-indigo-500' : ''" />
                </Link>

                <Link :href="route('profile.edit')" class="flex flex-col items-center p-2 rounded-xl transition-colors text-gray-400 hover:text-gray-700">
                    <GIcon name="Settings" :size="24" />
                </Link>

            </div>
        </nav>

    </div>
</template>
