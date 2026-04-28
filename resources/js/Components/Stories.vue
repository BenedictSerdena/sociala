<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({
    stories: Array,
    currentUser: Object,
});

const emit = defineEmits(['open-story']);
const fileInput = ref(null);
const uploading = ref(false);

const myStory = computed(() => props.stories.find(s => s.user.id === props.currentUser.id) ?? null);
const otherStories = computed(() => props.stories.filter(s => s.user.id !== props.currentUser.id));

function onOwnClick() {
    if (myStory.value) {
        emit('open-story', myStory.value);
    } else {
        fileInput.value?.click();
    }
}

function addNewStory() {
    fileInput.value?.click();
}

async function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    uploading.value = true;
    const form = useForm({ image: file });
    form.post(route('stories.store'), {
        forceFormData: true,
        onFinish: () => { uploading.value = false; e.target.value = ''; },
    });
}
</script>

<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm px-4 py-3">
        <div class="flex gap-4 overflow-x-auto pb-1" style="scrollbar-width:none; -ms-overflow-style:none;">

            <!-- Your Story -->
            <div class="flex flex-col items-center gap-1.5 flex-shrink-0">
                <div class="relative cursor-pointer group" @click="onOwnClick">
                    <div class="w-16 h-16 rounded-full p-0.5"
                         :class="myStory ? 'bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500' : 'bg-gray-200 dark:bg-gray-700'">
                        <div class="w-full h-full rounded-full overflow-hidden border-2 border-white dark:border-gray-900 relative">
                            <img :src="currentUser.avatar_url" class="w-full h-full object-cover" />
                            <div v-if="!myStory"
                                 class="absolute inset-0 bg-black/20 flex items-center justify-center group-hover:bg-black/30 transition-all">
                                <GIcon name="PhotoCamera" :size="18" class="text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Add new story button (when story exists) -->
                    <button v-if="myStory"
                            @click.stop="addNewStory"
                            class="absolute -bottom-0.5 -right-0.5 w-5 h-5 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-900 shadow-sm hover:scale-110 transition-transform">
                        <GIcon name="Plus" :size="10" class="text-white" />
                    </button>

                    <!-- No story: plus badge -->
                    <div v-if="!myStory"
                         class="absolute -bottom-0.5 -right-0.5 w-5 h-5 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-full flex items-center justify-center border-2 border-white dark:border-gray-900 shadow-sm">
                        <GIcon name="Plus" :size="10" class="text-white" />
                    </div>

                    <!-- Upload spinner -->
                    <div v-if="uploading"
                         class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>
                <span class="text-[11px] text-gray-600 dark:text-gray-400 font-medium truncate w-16 text-center">
                    {{ myStory ? 'Your story' : 'Add story' }}
                </span>
                <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
            </div>

            <!-- Other Users' Stories -->
            <div v-for="story in otherStories" :key="story.id"
                 class="flex flex-col items-center gap-1.5 flex-shrink-0 cursor-pointer group"
                 @click="emit('open-story', story)">
                <div class="w-16 h-16 rounded-full p-0.5 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 group-hover:from-indigo-600 group-hover:to-pink-600 transition-all shadow-sm group-hover:shadow-md group-hover:scale-105 duration-200">
                    <div class="w-full h-full rounded-full overflow-hidden border-2 border-white dark:border-gray-900">
                        <img :src="story.user.avatar_url" class="w-full h-full object-cover" />
                    </div>
                </div>
                <span class="text-[11px] text-gray-600 dark:text-gray-400 font-medium truncate w-16 text-center">
                    {{ story.user.name.split(' ')[0] }}
                </span>
            </div>

            <!-- Empty state -->
            <div v-if="stories.length === 0"
                 class="flex items-center text-gray-400 dark:text-gray-500 text-xs ml-2 py-4">
                No stories yet — add yours!
            </div>
        </div>
    </div>
</template>
