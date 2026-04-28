<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';
import PostForm from '@/Components/PostForm.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import IncomingCallModal from '@/Components/IncomingCallModal.vue';
import CallModal from '@/Components/CallModal.vue';
import { useDarkMode } from '@/composables/useDarkMode.js';
import { useToast } from '@/composables/useToast.js';

const page = usePage();
const user = page.props.auth.user;
const unreadCount = ref(0);
const { dark, toggle: toggleDark } = useDarkMode();
const { show: showToast } = useToast();
const createOpen    = ref(false);
const incomingCall  = ref(null);  // { callType, channelName, caller }
const activeCall    = ref(false);
const activeCallType = ref('voice');
const activeChannel  = ref(null);
const activeCallee   = ref(null);

watch(() => page.props.flash, (flash) => {
    if (flash?.success) showToast(flash.success, 'success');
    if (flash?.error) showToast(flash.error, 'error');
}, { immediate: true, deep: true });

onMounted(async () => {
    try {
        const res = await axios.get(route('notifications.unread'));
        unreadCount.value = res.data.count;
        window.Echo.private(`notifications.${user.id}`)
            .listen('NewNotification', () => { unreadCount.value++; });
    } catch {}

    // Listen for incoming calls from anyone
    try {
        window.Echo.private(`call.${user.id}`)
            .listen('.CallSignal', (e) => {
                if (e.type === 'incoming') {
                    incomingCall.value = {
                        callType:    e.callType,
                        channelName: e.channelName,
                        caller:      e.caller,
                    };
                } else if (e.type === 'ended' || e.type === 'declined') {
                    incomingCall.value = null;
                    activeCall.value   = false;
                }
            });
    } catch {}
});

async function acceptCall() {
    const call = incomingCall.value;
    incomingCall.value   = null;
    activeCallee.value   = call.caller;
    activeCallType.value = call.callType;
    activeChannel.value  = call.channelName;
    activeCall.value     = true;
    // Notify caller we accepted
    await axios.post(route('call.signal'), {
        to:        call.caller.id,
        type:      'accepted',
        call_type: call.callType,
        channel:   call.channelName,
    }).catch(() => {});
}

async function declineCall() {
    const call = incomingCall.value;
    incomingCall.value = null;
    await axios.post(route('call.signal'), {
        to:        call.caller.id,
        type:      'declined',
        call_type: call.callType,
        channel:   call.channelName,
    }).catch(() => {});
}

const isActive = (prefix) => page.url.startsWith(prefix);
const isProfile = () => page.url.includes(`/${user.username}`);
</script>

<template>
    <div class="min-h-screen bg-[#F5F5F7] dark:bg-gray-950 transition-colors duration-200">

        <ToastContainer />

        <IncomingCallModal :call="incomingCall" @accept="acceptCall" @decline="declineCall" />

        <CallModal :open="activeCall" :type="activeCallType" :channel-name="activeChannel"
                   :chat-user="activeCallee" :current-user="user"
                   @close="activeCall = false" />

        <!-- ─── Desktop Sidebar ─── -->
        <aside class="hidden lg:flex flex-col fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 border-r border-gray-200/80 dark:border-gray-800 z-40 px-4 py-6">

            <!-- Logo -->
            <Link :href="route('feed')" class="flex items-center gap-3 px-2 mb-8 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg shadow-purple-200 dark:shadow-purple-900/40 group-hover:shadow-purple-300 dark:group-hover:shadow-purple-800/60 transition-shadow">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <circle cx="9" cy="10" r="1" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="10" r="1" fill="currentColor" stroke="none"/>
                        <circle cx="15" cy="10" r="1" fill="currentColor" stroke="none"/>
                    </svg>
                </div>
                <span class="text-xl font-black tracking-tight bg-gradient-to-r from-violet-600 to-pink-500 bg-clip-text text-transparent">Sociala</span>
            </Link>

            <!-- Nav -->
            <nav class="flex flex-col gap-1 flex-1">

                <Link :href="route('feed')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/feed') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <GIcon name="Home" :size="20" :filled="isActive('/feed')" />
                    Home
                </Link>

                <Link :href="route('explore')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/explore') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <GIcon name="PlusCircle" :size="20" :filled="isActive('/explore')" />
                    Explore
                </Link>

                <Link :href="route('search')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/search') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <GIcon name="Search" :size="20" :filled="isActive('/search')" />
                    Search
                </Link>

                <Link :href="route('messages.index')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/messages') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <GIcon name="AnnotationDots" :size="20" :filled="isActive('/messages')" />
                    Messages
                </Link>

                <Link :href="route('notifications.index')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm relative"
                      :class="isActive('/notifications') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <div class="relative">
                        <GIcon name="Notification" :size="20" :filled="isActive('/notifications')" />
                        <span v-if="unreadCount > 0"
                              class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-bold min-w-[16px] h-4 px-0.5 rounded-full flex items-center justify-center">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </div>
                    Notifications
                </Link>

                <Link :href="route('bookmarks.index')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/bookmarks') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <GIcon name="Bookmark" :size="20" :filled="isActive('/bookmarks')" />
                    Saved
                </Link>

                <Link :href="route('profile.show', { user: user.username })"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isProfile() ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <img :src="user.avatar_url" class="w-5 h-5 rounded-full object-cover ring-1 ring-indigo-200" />
                    Profile
                </Link>

                <!-- Desktop Create Post button -->
                <button @click="createOpen = true"
                        class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm mt-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:opacity-90 shadow-sm">
                    <GIcon name="Plus" :size="20" />
                    New Post
                </button>

                <Link :href="route('profile.edit')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm"
                      :class="isActive('/profile/edit') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                    <GIcon name="Settings" :size="20" />
                    Settings
                </Link>

                <Link v-if="user.is_admin" :href="route('admin.dashboard')"
                      class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl transition-all font-medium text-sm bg-slate-800 dark:bg-slate-700 text-white hover:bg-slate-700 dark:hover:bg-slate-600">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Admin Panel
                </Link>

            </nav>

            <!-- User Footer -->
            <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                <div class="flex items-center gap-3 px-2 mb-3">
                    <img :src="user.avatar_url" class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                        <p class="text-xs text-gray-400 truncate">@{{ user.username }}</p>
                    </div>
                    <button @click="toggleDark"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all flex-shrink-0">
                        <svg v-if="dark" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V4a1 1 0 0 1 1-1zm0 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm7-5a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1zM3 11a1 1 0 1 0 0 2H2a1 1 0 1 0 0-2h1zm15.07-6.07a1 1 0 0 1 0 1.414l-.707.707a1 1 0 1 1-1.414-1.414l.707-.707a1 1 0 0 1 1.414 0zM6.636 17.364a1 1 0 0 1 0 1.414l-.707.707a1 1 0 1 1-1.414-1.414l.707-.707a1 1 0 0 1 1.414 0zM20 19a1 1 0 0 1-1.414 0l-.707-.707a1 1 0 1 1 1.414-1.414l.707.707A1 1 0 0 1 20 19zM7.05 6.05a1 1 0 0 1-1.414 0l-.707-.707A1 1 0 1 1 6.343 3.93l.707.707a1 1 0 0 1 0 1.414z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                        </svg>
                    </button>
                </div>
                <Link :href="route('logout')" method="post" as="button"
                      class="flex items-center gap-3 px-3 py-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-950 hover:text-red-500 transition-all w-full text-sm font-medium">
                    <GIcon name="LogOut" :size="18" />
                    Log out
                </Link>
            </div>
        </aside>

        <!-- ─── Mobile Top Bar ─── -->
        <header class="lg:hidden bg-white dark:bg-gray-900 border-b border-gray-200/80 dark:border-gray-800 sticky top-0 z-40 shadow-sm">
            <div class="flex items-center justify-between px-4 h-14">
                <Link :href="route('feed')" class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-violet-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-md">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <circle cx="9" cy="10" r="1" fill="currentColor" stroke="none"/>
                            <circle cx="12" cy="10" r="1" fill="currentColor" stroke="none"/>
                            <circle cx="15" cy="10" r="1" fill="currentColor" stroke="none"/>
                        </svg>
                    </div>
                    <span class="text-xl font-black tracking-tight bg-gradient-to-r from-violet-600 to-pink-500 bg-clip-text text-transparent">Sociala</span>
                </Link>
                <div class="flex items-center gap-1">
                    <button @click="toggleDark"
                            class="p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg v-if="dark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V4a1 1 0 0 1 1-1zm0 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm7-5a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1zM3 11a1 1 0 1 0 0 2H2a1 1 0 1 0 0-2h1zm15.07-6.07a1 1 0 0 1 0 1.414l-.707.707a1 1 0 1 1-1.414-1.414l.707-.707a1 1 0 0 1 1.414 0zM6.636 17.364a1 1 0 0 1 0 1.414l-.707.707a1 1 0 1 1-1.414-1.414l.707-.707a1 1 0 0 1 1.414 0zM20 19a1 1 0 0 1-1.414 0l-.707-.707a1 1 0 1 1 1.414-1.414l.707.707A1 1 0 0 1 20 19zM7.05 6.05a1 1 0 0 1-1.414 0l-.707-.707A1 1 0 1 1 6.343 3.93l.707.707a1 1 0 0 1 0 1.414z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                        </svg>
                    </button>
                    <Link :href="route('notifications.index')" class="relative p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <GIcon name="Notification" :size="22" :filled="isActive('/notifications')" class="text-gray-700 dark:text-gray-300" />
                        <span v-if="unreadCount > 0"
                              class="absolute top-1 right-1 bg-red-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- ─── Main Content ─── -->
        <main class="lg:ml-64 min-h-screen">
            <div class="max-w-5xl mx-auto px-4 py-6 pb-24 lg:pb-8">
                <slot />
            </div>
        </main>

        <!-- ─── Mobile Bottom Nav ─── -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200/80 dark:border-gray-800 z-40 safe-area-bottom shadow-lg">
            <div class="flex items-center justify-around h-14 px-2">

                <Link :href="route('feed')" class="flex flex-col items-center p-2 rounded-xl transition-colors"
                      :class="isActive('/feed') ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                    <GIcon name="Home" :size="24" :filled="isActive('/feed')" />
                </Link>

                <Link :href="route('explore')" class="flex flex-col items-center p-2 rounded-xl transition-colors"
                      :class="isActive('/explore') ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                    <GIcon name="PlusCircle" :size="24" :filled="isActive('/explore')" />
                </Link>

                <!-- Center Create Button -->
                <button @click="createOpen = true"
                        class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 active:scale-95 transition-all -mt-2">
                    <GIcon name="Plus" :size="24" />
                </button>

                <Link :href="route('messages.index')" class="flex flex-col items-center p-2 rounded-xl transition-colors"
                      :class="isActive('/messages') ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'">
                    <GIcon name="AnnotationDots" :size="24" :filled="isActive('/messages')" />
                </Link>

                <Link :href="route('profile.show', { user: user.username })" class="flex flex-col items-center p-2"
                      :class="isProfile() ? 'text-indigo-600' : 'text-gray-400'">
                    <img :src="user.avatar_url" class="w-7 h-7 rounded-full object-cover" :class="isProfile() ? 'ring-2 ring-indigo-500' : ''" />
                </Link>

            </div>
        </nav>

        <!-- ─── Create Post Modal (desktop + mobile) ─── -->
        <Teleport to="body">
            <div v-if="createOpen"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
                 @click.self="createOpen = false">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="createOpen = false"></div>
                <div class="relative w-full sm:w-[520px] sm:rounded-2xl rounded-t-2xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Create Post</h3>
                        <button @click="createOpen = false"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                            <GIcon name="Close" :size="18" />
                        </button>
                    </div>
                    <div class="p-4">
                        <PostForm @posted="createOpen = false" />
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>
