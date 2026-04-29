<script setup>
import { ref, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import GIcon from '@/Components/GIcon.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useDarkMode } from '@/composables/useDarkMode.js';
import { useToast } from '@/composables/useToast.js';

const props = defineProps({ user: Object, status: String, mustVerifyEmail: Boolean });
const { dark, toggle: toggleDark } = useDarkMode();
const { show: toast } = useToast();

const activeTab = ref('profile');
const showDeleteModal = ref(false);
const deletePasswordInput = ref(null);

const tabs = [
    { id: 'profile',    label: 'Profile',     icon: 'User',       color: 'indigo' },
    { id: 'account',    label: 'Account',     icon: 'Settings',   color: 'blue'   },
    { id: 'appearance', label: 'Appearance',  icon: 'Sun',        color: 'amber'  },
    { id: 'privacy',    label: 'Privacy',     icon: 'Lock',       color: 'green'  },
    { id: 'danger',     label: 'Danger Zone', icon: 'Trash',      color: 'red'    },
];

// ── Profile form ──
const avatarPreview = ref(props.user.avatar_url);
const coverPreview = ref(props.user.cover_photo ?? null);
const profileForm = useForm({
    name: props.user.name,
    username: props.user.username,
    bio: props.user.bio ?? '',
    avatar: null,
    cover_photo: null,
});

function onAvatarChange(e) {
    const file = e.target.files[0];
    if (file) { profileForm.avatar = file; avatarPreview.value = URL.createObjectURL(file); }
}
function onCoverChange(e) {
    const file = e.target.files[0];
    if (file) { profileForm.cover_photo = file; coverPreview.value = URL.createObjectURL(file); }
}
function submitProfile() {
    profileForm.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => toast('Profile updated!', 'success'),
    });
}

// ── Password form ──
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
function submitPassword() {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => { passwordForm.reset(); toast('Password changed!', 'success'); },
    });
}

// ── Delete account ──
const deleteForm = useForm({ password: '' });
function openDeleteModal() {
    showDeleteModal.value = true;
    nextTick(() => deletePasswordInput.value?.focus());
}
function deleteAccount() {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onError: () => deletePasswordInput.value?.focus(),
        onFinish: () => deleteForm.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">

            <!-- Page title -->
            <div class="mb-6">
                <h1 class="text-xl font-black text-gray-900 dark:text-white">Settings</h1>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Manage your account, appearance, and privacy</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">

                <!-- ── Sidebar (desktop) / Tab row (mobile) ── -->
                <aside class="lg:w-52 flex-shrink-0">

                    <!-- Mobile: horizontal scroll tabs -->
                    <div class="lg:hidden flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                        <button v-for="tab in tabs" :key="tab.id"
                                @click="activeTab = tab.id"
                                class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold transition-all"
                                :class="activeTab === tab.id
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-800'">
                            <GIcon :name="tab.icon" :size="15" />
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Desktop: vertical nav -->
                    <nav class="hidden lg:flex flex-col gap-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-2">
                        <button v-for="tab in tabs" :key="tab.id"
                                @click="activeTab = tab.id"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all text-left w-full"
                                :class="activeTab === tab.id
                                    ? tab.id === 'danger'
                                        ? 'bg-red-50 dark:bg-red-950 text-red-600 dark:text-red-400'
                                        : 'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400'
                                    : tab.id === 'danger'
                                        ? 'text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-950 hover:text-red-500'
                                        : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white'">
                            <GIcon :name="tab.icon" :size="18" />
                            {{ tab.label }}
                        </button>
                    </nav>
                </aside>

                <!-- ── Content panel ── -->
                <div class="flex-1 min-w-0">

                    <!-- ━━━ PROFILE ━━━ -->
                    <div v-if="activeTab === 'profile'"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm overflow-hidden">

                        <!-- Cover photo -->
                        <div class="relative h-40">
                            <div v-if="!coverPreview"
                                 class="w-full h-full bg-gradient-to-br from-indigo-400 via-purple-400 to-pink-400"></div>
                            <img v-else :src="coverPreview" class="w-full h-full object-cover" />
                            <label class="absolute inset-0 flex items-center justify-center bg-black/30 cursor-pointer hover:bg-black/45 transition-all">
                                <div class="text-white text-center">
                                    <svg class="w-7 h-7 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="text-xs font-semibold">Change Cover</span>
                                </div>
                                <input type="file" class="hidden" accept="image/*" @change="onCoverChange" />
                            </label>
                        </div>

                        <div class="px-6 pb-6">
                            <!-- Avatar -->
                            <div class="-mt-10 mb-6 flex items-end gap-4">
                                <div class="relative">
                                    <img :src="avatarPreview" class="w-20 h-20 rounded-full border-4 border-white dark:border-gray-900 object-cover shadow-lg" />
                                    <label class="absolute inset-0 rounded-full flex items-center justify-center bg-black/0 hover:bg-black/40 cursor-pointer transition-all group">
                                        <svg class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <input type="file" class="hidden" accept="image/*" @change="onAvatarChange" />
                                    </label>
                                </div>
                                <div class="pb-1">
                                    <p class="font-bold text-gray-900 dark:text-white text-sm">{{ profileForm.name || 'Your Name' }}</p>
                                    <p class="text-gray-400 text-xs">@{{ profileForm.username || 'username' }}</p>
                                </div>
                            </div>

                            <form @submit.prevent="submitProfile" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Display Name</label>
                                        <input v-model="profileForm.name" type="text"
                                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white dark:focus:bg-gray-700 transition-all"
                                               placeholder="Your full name" />
                                        <p v-if="profileForm.errors.name" class="text-red-500 text-xs mt-1">{{ profileForm.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Username</label>
                                        <div class="flex items-center border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-indigo-400 focus-within:bg-white dark:focus-within:bg-gray-700 transition-all">
                                            <span class="px-3 py-2.5 text-gray-400 text-sm border-r border-gray-200 dark:border-gray-700">@</span>
                                            <input v-model="profileForm.username" type="text"
                                                   class="flex-1 px-3 py-2.5 text-sm outline-none bg-transparent text-gray-900 dark:text-white"
                                                   placeholder="your_username" />
                                        </div>
                                        <p v-if="profileForm.errors.username" class="text-red-500 text-xs mt-1">{{ profileForm.errors.username }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Bio</label>
                                    <textarea v-model="profileForm.bio" rows="3" maxlength="300"
                                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white dark:focus:bg-gray-700 transition-all"
                                              placeholder="Tell people about yourself…"></textarea>
                                    <p class="text-xs mt-1 text-right"
                                       :class="profileForm.bio.length >= 300 ? 'text-red-500 font-semibold' : profileForm.bio.length >= 280 ? 'text-orange-400' : 'text-gray-400'">
                                        {{ profileForm.bio.length }}/300
                                    </p>
                                </div>

                                <div class="flex justify-end pt-1">
                                    <button type="submit" :disabled="profileForm.processing"
                                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:opacity-90 disabled:opacity-50 transition-all shadow-sm">
                                        {{ profileForm.processing ? 'Saving…' : 'Save Profile' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ━━━ ACCOUNT ━━━ -->
                    <div v-if="activeTab === 'account'" class="space-y-4">

                        <!-- Email section -->
                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950 flex items-center justify-center">
                                    <GIcon name="Mail" :size="18" class="text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Email Address</h3>
                                    <p class="text-gray-400 dark:text-gray-500 text-xs">Your login email</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <GIcon name="Mail" :size="16" class="text-gray-400 flex-shrink-0" />
                                <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ user.email }}</span>
                                <span v-if="user.email_verified_at"
                                      class="ml-auto flex items-center gap-1 text-xs text-green-600 dark:text-green-400 font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Verified
                                </span>
                                <span v-else class="ml-auto text-xs text-orange-500 font-semibold">Not verified</span>
                            </div>
                        </div>

                        <!-- Change password -->
                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center">
                                    <GIcon name="Lock" :size="18" class="text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Change Password</h3>
                                    <p class="text-gray-400 dark:text-gray-500 text-xs">Use a strong, unique password</p>
                                </div>
                            </div>

                            <form @submit.prevent="submitPassword" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Current Password</label>
                                    <input v-model="passwordForm.current_password" type="password"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white dark:focus:bg-gray-700 transition-all"
                                           placeholder="••••••••" autocomplete="current-password" />
                                    <p v-if="passwordForm.errors.current_password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.current_password }}</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">New Password</label>
                                        <input v-model="passwordForm.password" type="password"
                                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white dark:focus:bg-gray-700 transition-all"
                                               placeholder="••••••••" autocomplete="new-password" />
                                        <p v-if="passwordForm.errors.password" class="text-red-500 text-xs mt-1">{{ passwordForm.errors.password }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Confirm Password</label>
                                        <input v-model="passwordForm.password_confirmation" type="password"
                                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white dark:focus:bg-gray-700 transition-all"
                                               placeholder="••••••••" autocomplete="new-password" />
                                    </div>
                                </div>

                                <div class="flex justify-end pt-1">
                                    <button type="submit" :disabled="passwordForm.processing"
                                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:opacity-90 disabled:opacity-50 transition-all shadow-sm">
                                        {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ━━━ APPEARANCE ━━━ -->
                    <div v-if="activeTab === 'appearance'"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-6 space-y-5">

                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950 flex items-center justify-center">
                                <GIcon name="Sun" :size="18" class="text-amber-500" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Appearance</h3>
                                <p class="text-gray-400 dark:text-gray-500 text-xs">Customize how Sociala looks</p>
                            </div>
                        </div>

                        <!-- Dark mode toggle -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <svg v-if="dark" class="w-4 h-4 text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 3a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V4a1 1 0 0 1 1-1zm0 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm7-5a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1zM3 11a1 1 0 1 0 0 2H2a1 1 0 1 0 0-2h1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Dark Mode</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ dark ? 'Currently on' : 'Currently off' }}</p>
                                </div>
                            </div>
                            <!-- Toggle switch -->
                            <button @click="toggleDark"
                                    class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none"
                                    :class="dark ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'">
                                <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200"
                                      :class="dark ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>

                        <!-- Theme preview cards -->
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Theme Preview</p>
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Light -->
                                <button @click="dark && toggleDark()"
                                        class="relative p-3 rounded-xl border-2 transition-all"
                                        :class="!dark ? 'border-indigo-500 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <div class="bg-[#F5F5F7] rounded-lg p-2 space-y-1.5 mb-2">
                                        <div class="bg-white rounded h-2 w-full shadow-sm"></div>
                                        <div class="bg-white rounded h-5 w-full shadow-sm"></div>
                                        <div class="bg-white rounded h-5 w-full shadow-sm"></div>
                                    </div>
                                    <p class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300">Light</p>
                                    <span v-if="!dark" class="absolute top-2 right-2 w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </button>

                                <!-- Dark -->
                                <button @click="!dark && toggleDark()"
                                        class="relative p-3 rounded-xl border-2 transition-all"
                                        :class="dark ? 'border-indigo-500 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <div class="bg-gray-900 rounded-lg p-2 space-y-1.5 mb-2">
                                        <div class="bg-gray-800 rounded h-2 w-full"></div>
                                        <div class="bg-gray-800 rounded h-5 w-full"></div>
                                        <div class="bg-gray-800 rounded h-5 w-full"></div>
                                    </div>
                                    <p class="text-xs font-semibold text-center text-gray-700 dark:text-gray-300">Dark</p>
                                    <span v-if="dark" class="absolute top-2 right-2 w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ━━━ PRIVACY ━━━ -->
                    <div v-if="activeTab === 'privacy'"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm p-6 space-y-5">

                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-xl bg-green-50 dark:bg-green-950 flex items-center justify-center">
                                <GIcon name="Lock" :size="18" class="text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Privacy</h3>
                                <p class="text-gray-400 dark:text-gray-500 text-xs">Control who can see your content</p>
                            </div>
                        </div>

                        <!-- Privacy rows -->
                        <div class="space-y-3">
                            <div v-for="item in [
                                { label: 'Public Profile', desc: 'Anyone can view your profile and posts', icon: 'Users', enabled: true },
                                { label: 'Show in Search', desc: 'Your account appears in search results', icon: 'Search', enabled: true },
                                { label: 'Allow Messages', desc: 'Anyone can send you a direct message', icon: 'AnnotationDots', enabled: true },
                                { label: 'Show Activity Status', desc: 'Others can see when you were last active', icon: 'CheckCircle', enabled: false },
                            ]" :key="item.label"
                                 class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center">
                                        <GIcon :name="item.icon" :size="15" class="text-gray-500 dark:text-gray-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.label }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ item.desc }}</p>
                                    </div>
                                </div>
                                <div class="relative w-11 h-6 rounded-full transition-colors duration-200 flex-shrink-0"
                                     :class="item.enabled ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-600'">
                                    <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200"
                                          :class="item.enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-gray-400 dark:text-gray-500 text-center pt-1">
                            Full privacy controls coming soon
                        </p>
                    </div>

                    <!-- ━━━ DANGER ZONE ━━━ -->
                    <div v-if="activeTab === 'danger'"
                         class="bg-white dark:bg-gray-900 rounded-2xl border border-red-200 dark:border-red-900/50 shadow-sm p-6 space-y-5">

                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-950 flex items-center justify-center">
                                <GIcon name="Trash" :size="18" class="text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="font-bold text-red-600 dark:text-red-400 text-sm">Danger Zone</h3>
                                <p class="text-gray-400 dark:text-gray-500 text-xs">Irreversible and destructive actions</p>
                            </div>
                        </div>

                        <div class="p-4 bg-red-50 dark:bg-red-950/40 rounded-xl border border-red-100 dark:border-red-900/50">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">Delete Account</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed max-w-xs">
                                        Once deleted, all your posts, followers, messages and data will be permanently removed. This cannot be undone.
                                    </p>
                                </div>
                                <button @click="openDeleteModal"
                                        class="flex-shrink-0 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Delete Confirmation Modal ── -->
        <Teleport to="body">
            <div v-if="showDeleteModal"
                 class="fixed inset-0 z-50 flex items-center justify-center px-4"
                 @click.self="showDeleteModal = false">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl shadow-2xl p-6">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-950 flex items-center justify-center mx-auto mb-4">
                        <GIcon name="Trash" :size="22" class="text-red-600 dark:text-red-400" />
                    </div>
                    <h3 class="text-center font-bold text-gray-900 dark:text-white text-lg mb-1">Delete your account?</h3>
                    <p class="text-center text-gray-400 dark:text-gray-500 text-sm mb-6">
                        All your posts, messages, and followers will be permanently deleted.
                    </p>
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">Enter your password to confirm</label>
                        <input ref="deletePasswordInput"
                               v-model="deleteForm.password"
                               type="password"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-400 transition-all"
                               placeholder="••••••••"
                               @keyup.enter="deleteAccount" />
                        <p v-if="deleteForm.errors.password" class="text-red-500 text-xs mt-1">{{ deleteForm.errors.password }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false"
                                class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                            Cancel
                        </button>
                        <button @click="deleteAccount" :disabled="deleteForm.processing || !deleteForm.password"
                                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold transition-all disabled:opacity-50">
                            {{ deleteForm.processing ? 'Deleting…' : 'Yes, Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
