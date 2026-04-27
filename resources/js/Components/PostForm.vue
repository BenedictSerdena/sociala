<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user;

const form = useForm({ content: '', image: null });
const previewUrl = ref(null);

function onFileChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
}

function clearImage() {
    form.image = null;
    previewUrl.value = null;
}

function submit() {
    form.post(route('posts.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            previewUrl.value = null;
        },
    });
}
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex gap-3">
            <img :src="user.avatar_url || `https://ui-avatars.com/api/?name=${user.name}&background=6366f1&color=fff`"
                 class="w-10 h-10 rounded-full object-cover flex-shrink-0" />
            <div class="flex-1">
                <textarea v-model="form.content" placeholder="What's on your mind?"
                          class="w-full resize-none border-none outline-none text-gray-800 placeholder-gray-400 text-sm"
                          rows="3"></textarea>

                <img v-if="previewUrl" :src="previewUrl" class="mt-2 rounded-lg max-h-48 object-cover" />

                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                    <label class="cursor-pointer flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Photo
                        <input type="file" class="hidden" accept="image/*" @change="onFileChange" />
                    </label>
                    <button v-if="previewUrl" @click="clearImage" class="text-xs text-red-400 hover:text-red-600">Remove photo</button>
                    <button @click="submit" :disabled="form.processing || !form.content.trim()"
                            class="bg-indigo-600 text-white text-sm px-5 py-1.5 rounded-full hover:bg-indigo-700 disabled:opacity-40 transition-colors">
                        Post
                    </button>
                </div>
                <p v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content }}</p>
            </div>
        </div>
    </div>
</template>
