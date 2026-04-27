<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({
    stories: Array,
    startIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close']);

const current = ref(props.startIndex);
const progress = ref(0);
let timer = null;
let progressTimer = null;
const DURATION = 5000;

const story = computed(() => props.stories[current.value]);

function startProgress() {
    clearInterval(progressTimer);
    clearTimeout(timer);
    progress.value = 0;
    const steps = 50;
    const interval = DURATION / steps;
    progressTimer = setInterval(() => {
        progress.value += (100 / steps);
        if (progress.value >= 100) {
            clearInterval(progressTimer);
        }
    }, interval);
    timer = setTimeout(next, DURATION);
}

function next() {
    if (current.value < props.stories.length - 1) {
        current.value++;
        startProgress();
    } else {
        emit('close');
    }
}

function prev() {
    if (current.value > 0) {
        current.value--;
        startProgress();
    }
}

function formatTime(date) {
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
}

function onKeyDown(e) {
    if (e.key === 'Escape') emit('close');
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
}

onMounted(() => {
    startProgress();
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    clearInterval(progressTimer);
    clearTimeout(timer);
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black">

        <!-- Progress bars -->
        <div class="absolute top-4 left-4 right-4 flex gap-1 z-10">
            <div v-for="(s, i) in stories" :key="s.id" class="h-0.5 flex-1 bg-white bg-opacity-30 rounded-full overflow-hidden">
                <div class="h-full bg-white rounded-full transition-none"
                     :style="{ width: i < current ? '100%' : i === current ? progress + '%' : '0%' }"></div>
            </div>
        </div>

        <!-- Header -->
        <div class="absolute top-8 left-4 right-4 flex items-center justify-between z-10">
            <div class="flex items-center gap-3">
                <img :src="story.user.avatar_url" class="w-10 h-10 rounded-full border-2 border-white object-cover" />
                <div>
                    <p class="text-white font-semibold text-sm">{{ story.user.name }}</p>
                    <p class="text-white text-opacity-70 text-xs">{{ formatTime(story.created_at) }}</p>
                </div>
            </div>
            <button @click="emit('close')" class="text-white p-2 hover:bg-white hover:bg-opacity-10 rounded-full transition-colors">
                <GIcon name="Close" :size="22" class="text-white" />
            </button>
        </div>

        <!-- Story Image -->
        <img :src="story.image_url" class="max-h-screen max-w-sm w-full object-contain" />

        <!-- Tap zones -->
        <button @click="prev" class="absolute left-0 top-0 w-1/3 h-full opacity-0" aria-label="Previous story" />
        <button @click="next" class="absolute right-0 top-0 w-1/3 h-full opacity-0" aria-label="Next story" />
    </div>
</template>
