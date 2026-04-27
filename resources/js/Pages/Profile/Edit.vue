<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
    status: String,
    mustVerifyEmail: Boolean,
});

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
        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Edit Profile</h2>

            <div v-if="status" class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm">{{ status }}</div>

            <form @submit.prevent="submit" class="space-y-5" enctype="multipart/form-data">
                <!-- Avatar -->
                <div class="flex items-center gap-4">
                    <img :src="avatarPreview" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-200" />
                    <label class="cursor-pointer text-sm text-indigo-600 hover:underline">
                        Change Avatar
                        <input type="file" class="hidden" accept="image/*" @change="onAvatarChange" />
                    </label>
                </div>

                <!-- Cover Photo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cover Photo</label>
                    <div class="h-24 rounded-lg overflow-hidden bg-gradient-to-r from-indigo-400 to-purple-500 relative">
                        <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" />
                        <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 cursor-pointer text-white text-sm">
                            Change Cover
                            <input type="file" class="hidden" accept="image/*" @change="onCoverChange" />
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-indigo-300">
                        <span class="bg-gray-50 px-3 py-2 text-gray-400 text-sm border-r border-gray-300">@</span>
                        <input v-model="form.username" type="text" class="flex-1 px-3 py-2 text-sm outline-none" />
                    </div>
                    <p v-if="form.errors.username" class="text-red-500 text-xs mt-1">{{ form.errors.username }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea v-model="form.bio" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="Tell people about yourself…"></textarea>
                    <p v-if="form.errors.bio" class="text-red-500 text-xs mt-1">{{ form.errors.bio }}</p>
                </div>

                <button type="submit" :disabled="form.processing"
                        class="w-full bg-indigo-600 text-white py-2 rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                    Save Changes
                </button>
            </form>
        </div>
    </AppLayout>
</template>
