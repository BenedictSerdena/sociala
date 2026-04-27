<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;
const unreadCount = ref(0);
const showMobileMenu = ref(false);

onMounted(async () => {
    const res = await axios.get(route('notifications.unread'));
    unreadCount.value = res.data.count;

    window.Echo.private(`notifications.${user.id}`)
        .listen('NewNotification', () => {
            unreadCount.value++;
        });
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Nav -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-4 flex items-center justify-between h-14">
                <Link :href="route('feed')" class="text-xl font-bold text-indigo-600">SocialApp</Link>

                <div class="hidden md:flex items-center gap-6 text-sm">
                    <Link :href="route('feed')" class="text-gray-600 hover:text-indigo-600 font-medium">Feed</Link>
                    <Link :href="route('messages.index')" class="text-gray-600 hover:text-indigo-600 font-medium">Messages</Link>
                    <Link :href="route('notifications.index')" class="relative text-gray-600 hover:text-indigo-600 font-medium">
                        Notifications
                        <span v-if="unreadCount > 0" class="absolute -top-2 -right-3 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </Link>
                    <Link :href="route('profile.show', { user: user.username })" class="flex items-center gap-2 text-gray-700 hover:text-indigo-600">
                        <img :src="user.avatar_url || `https://ui-avatars.com/api/?name=${user.name}&background=6366f1&color=fff`" class="w-7 h-7 rounded-full object-cover" />
                        <span class="font-medium">{{ user.name }}</span>
                    </Link>
                    <Link :href="route('profile.edit')" class="text-gray-500 hover:text-indigo-600 text-xs">Settings</Link>
                    <Link :href="route('logout')" method="post" as="button" class="text-gray-500 hover:text-red-500 text-xs">Logout</Link>
                </div>

                <button @click="showMobileMenu = !showMobileMenu" class="md:hidden p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <div v-if="showMobileMenu" class="md:hidden bg-white border-t border-gray-100 px-4 py-3 flex flex-col gap-3 text-sm">
                <Link :href="route('feed')" class="text-gray-700">Feed</Link>
                <Link :href="route('messages.index')" class="text-gray-700">Messages</Link>
                <Link :href="route('notifications.index')" class="text-gray-700">Notifications <span v-if="unreadCount > 0" class="ml-1 bg-red-500 text-white text-xs px-1 rounded-full">{{ unreadCount }}</span></Link>
                <Link :href="route('profile.show', { user: user.username })" class="text-gray-700">My Profile</Link>
                <Link :href="route('profile.edit')" class="text-gray-700">Settings</Link>
                <Link :href="route('logout')" method="post" as="button" class="text-red-500">Logout</Link>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto px-4 py-6">
            <slot />
        </main>
    </div>
</template>
