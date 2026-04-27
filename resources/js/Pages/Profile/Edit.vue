<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ user: Object, status: String });

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    bio: props.user.bio ?? '',
    avatar: null,
    cover_photo: null,
});

const avatarPreview = ref(props.user.avatar_url);
const coverPreview = ref(props.user.cover_photo ? `/storage/${props.user.cover_photo}` : null);

function onAvatarChange(e) {
    const file = e.target.files[0];
    if (file) { form.avatar = file; avatarPreview.value = URL.createObjectURL(file); }
}

function onCoverChange(e) {
    const file = e.target.files[0];
    if (file) { form.cover_photo = file; coverPreview.value = URL.createObjectURL(file); }
}

function submit() {
    form.post(route('profile.update'), { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Cover Preview -->
                <div class="relative h-36">
                    <div v-if="!coverPreview" class="w-full h-full bg-gradient-to-br from-indigo-400 via-purple-400 to-pink-400"></div>
                    <img v-else :src="coverPreview" class="w-full h-full object-cover" />
                    <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 cursor-pointer group hover:bg-opacity-50 transition-all">
                        <div class="text-white text-center">
                            <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Change Cover</span>
                        </div>
                        <input type="file" class="hidden" accept="image/*" @change="onCoverChange" />
                    </label>
                </div>

                <div class="px-6 pb-6">
                    <!-- Avatar -->
                    <div class="-mt-12 mb-5 flex items-end gap-4">
                        <div class="relative">
                            <img :src="avatarPreview" class="w-24 h-24 rounded-full border-4 border-white object-cover shadow-lg" />
                            <label class="absolute inset-0 rounded-full flex items-center justify-center bg-black bg-opacity-0 hover:bg-opacity-40 cursor-pointer transition-all group">
                                <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <input type="file" class="hidden" accept="image/*" @change="onAvatarChange" />
                            </label>
                        </div>
                        <div class="pb-1">
                            <p class="font-bold text-gray-900">{{ form.name || 'Your Name' }}</p>
                            <p class="text-gray-400 text-sm">@{{ form.username || 'username' }}</p>
                        </div>
                    </div>

                    <div v-if="status" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium">
                        ✓ {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Display Name</label>
                            <input v-model="form.name" type="text"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-all"
                                   placeholder="Your full name" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
                            <div class="flex items-center border border-gray-200 bg-gray-50 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-indigo-400 focus-within:bg-white transition-all">
                                <span class="px-4 py-3 text-gray-400 text-sm font-medium border-r border-gray-200">@</span>
                                <input v-model="form.username" type="text"
                                       class="flex-1 px-4 py-3 text-sm outline-none bg-transparent"
                                       placeholder="your_username" />
                            </div>
                            <p v-if="form.errors.username" class="text-red-500 text-xs mt-1">{{ form.errors.username }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Bio</label>
                            <textarea v-model="form.bio" rows="3"
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-all"
                                      placeholder="Tell people about yourself…"></textarea>
                            <p class="text-xs text-gray-400 mt-1 text-right">{{ form.bio.length }}/300</p>
                            <p v-if="form.errors.bio" class="text-red-500 text-xs mt-1">{{ form.errors.bio }}</p>
                        </div>

                        <button type="submit" :disabled="form.processing"
                                class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 transition-all shadow-md">
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
