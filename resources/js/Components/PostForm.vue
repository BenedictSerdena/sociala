<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const page = usePage();
const user = page.props.auth.user;
const form = useForm({ content: '', image: null });
const previewUrl = ref(null);
const fileInput = ref(null);
const focused = ref(false);

function onFileChange(e) {
    const file = e.target.files[0];
    if (file) { form.image = file; previewUrl.value = URL.createObjectURL(file); }
}

function clearImage() {
    form.image = null;
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function submit() {
    form.post(route('posts.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { form.reset(); previewUrl.value = null; focused.value = false; },
    });
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-shadow duration-200"
         :class="focused ? 'shadow-md ring-1 ring-indigo-100' : ''">

        <div class="flex gap-3 p-4">
            <img :src="user.avatar_url" class="w-10 h-10 rounded-full object-cover flex-shrink-0 ring-2 ring-gray-100" />
            <div class="flex-1 min-w-0">
                <textarea v-model="form.content"
                          @focus="focused = true" @blur="focused = false"
                          placeholder="What's on your mind?"
                          class="w-full resize-none outline-none text-gray-800 placeholder-gray-400 text-sm leading-relaxed bg-transparent"
                          :rows="focused || form.content ? 3 : 1"></textarea>
            </div>
        </div>

        <!-- Image Preview -->
        <div v-if="previewUrl" class="relative mx-4 mb-3 rounded-xl overflow-hidden">
            <img :src="previewUrl" class="w-full max-h-64 object-cover" />
            <button @click="clearImage"
                    class="absolute top-2 right-2 bg-black bg-opacity-60 text-white w-7 h-7 rounded-full flex items-center justify-center hover:bg-opacity-80 transition-all">
                <GIcon name="Close" :size="14" />
            </button>
        </div>

        <!-- Bottom bar -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100/80 bg-gray-50/40">
            <label class="cursor-pointer flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 transition-colors px-3 py-1.5 rounded-lg hover:bg-indigo-50">
                <GIcon name="Image" :size="18" />
                <span class="font-medium text-xs">Photo</span>
                <input ref="fileInput" type="file" class="hidden" accept="image/*" @change="onFileChange" />
            </label>

            <button @click="submit" :disabled="form.processing || !form.content.trim()"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold px-5 py-2 rounded-full hover:from-indigo-700 hover:to-purple-700 disabled:opacity-40 transition-all shadow-sm flex items-center gap-2">
                <span>{{ form.processing ? 'Posting…' : 'Post' }}</span>
            </button>
        </div>

        <p v-if="form.errors.content" class="px-4 pb-3 text-red-500 text-xs">{{ form.errors.content }}</p>
    </div>
</template>
