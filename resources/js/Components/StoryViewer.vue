<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import GIcon from '@/Components/GIcon.vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    stories: Array,
    startIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'deleted', 'archived']);
const authUser = usePage().props.auth.user;

const current = ref(props.startIndex);
const progress = ref(0);
const paused = ref(false);
const menuOpen = ref(false);
const deleting = ref(false);
const archiving = ref(false);
const timeNow = ref(Date.now());

const DURATION = 5000;
let timer = null;
let progressTimer = null;
let clockTimer = null;

const story = computed(() => props.stories[current.value]);
const isOwn = computed(() => story.value?.user?.id === authUser.id);

function timeSince(date) {
    const diff = Math.floor((timeNow.value - new Date(date)) / 1000);
    const h = Math.floor(diff / 3600);
    const m = Math.floor((diff % 3600) / 60);
    if (h > 0) return `${h}h ago`;
    if (m > 0) return `${m}m ago`;
    return 'Just now';
}

function startProgress() {
    clearInterval(progressTimer);
    clearTimeout(timer);
    progress.value = 0;
    paused.value = false;
    menuOpen.value = false;

    const steps = 100;
    const interval = DURATION / steps;
    progressTimer = setInterval(() => {
        if (!paused.value) {
            progress.value += (100 / steps);
            if (progress.value >= 100) clearInterval(progressTimer);
        }
    }, interval);
    timer = setTimeout(() => {
        if (!paused.value) next();
    }, DURATION);
}

function pauseStory() { paused.value = true; }
function resumeStory() { paused.value = false; }

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

async function deleteStory() {
    if (deleting.value) return;
    deleting.value = true;
    menuOpen.value = false;
    await axios.delete(route('stories.destroy', story.value.id));
    emit('deleted', story.value.id);
    emit('close');
}

async function archiveStory() {
    if (archiving.value) return;
    archiving.value = true;
    menuOpen.value = false;
    await axios.post(route('stories.archive', story.value.id));
    emit('archived', story.value.id);
    emit('close');
}

function onKeyDown(e) {
    if (e.key === 'Escape') emit('close');
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
}

onMounted(() => {
    startProgress();
    window.addEventListener('keydown', onKeyDown);
    clockTimer = setInterval(() => { timeNow.value = Date.now(); }, 60000);
});

onUnmounted(() => {
    clearInterval(progressTimer);
    clearTimeout(timer);
    clearInterval(clockTimer);
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <!-- Full-screen backdrop -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm"
         @click.self="emit('close')">

        <!-- Phone-frame story container -->
        <div class="relative w-full max-w-[390px] mx-4"
             style="aspect-ratio: 9/16; max-height: 90vh;"
             @mousedown="pauseStory" @mouseup="resumeStory"
             @touchstart.prevent="pauseStory" @touchend="resumeStory">

            <!-- Story image -->
            <img :src="story.image_url"
                 class="absolute inset-0 w-full h-full object-cover rounded-2xl" />

            <!-- Top gradient overlay -->
            <div class="absolute inset-x-0 top-0 h-36 rounded-t-2xl"
                 style="background: linear-gradient(to bottom, rgba(0,0,0,0.65) 0%, transparent 100%)"></div>

            <!-- Bottom gradient overlay -->
            <div class="absolute inset-x-0 bottom-0 h-28 rounded-b-2xl"
                 style="background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 100%)"></div>

            <!-- Progress bars -->
            <div class="absolute top-3 left-3 right-3 flex gap-1 z-30">
                <div v-for="(s, i) in stories" :key="s.id"
                     class="h-[3px] flex-1 bg-white/30 rounded-full overflow-hidden">
                    <div class="h-full bg-white rounded-full transition-none"
                         :style="{ width: i < current ? '100%' : i === current ? progress + '%' : '0%' }"></div>
                </div>
            </div>

            <!-- Header: avatar + name + time -->
            <div class="absolute top-8 left-3 right-3 flex items-center justify-between z-30">
                <div class="flex items-center gap-2.5">
                    <img :src="story.user.avatar_url"
                         class="w-10 h-10 rounded-full border-2 border-white/80 object-cover shadow-lg" />
                    <div>
                        <p class="text-white font-bold text-sm drop-shadow">{{ story.user.name }}</p>
                        <p class="text-white/70 text-[11px] font-medium">{{ timeSince(story.created_at) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <!-- 3-dots menu (own story) -->
                    <div v-if="isOwn" class="relative">
                        <button @click.stop="menuOpen = !menuOpen; paused = menuOpen"
                                @mousedown.stop @touchstart.stop
                                class="text-white p-2 hover:bg-white/10 rounded-full transition-colors">
                            <GIcon name="MoreHorizontal" :size="20" class="text-white" />
                        </button>
                        <div v-if="menuOpen"
                             class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-20 border border-gray-100 dark:border-gray-700">
                            <button @click="archiveStory" :disabled="archiving"
                                    class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-3 disabled:opacity-40">
                                <GIcon name="Bookmark" :size="16" class="text-indigo-500" />
                                Archive story
                            </button>
                            <div class="h-px bg-gray-100 dark:bg-gray-700"></div>
                            <button @click="deleteStory" :disabled="deleting"
                                    class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors flex items-center gap-3 disabled:opacity-40">
                                <GIcon name="Trash" :size="16" />
                                Delete story
                            </button>
                        </div>
                    </div>

                    <!-- Close button -->
                    <button @click="emit('close')" @mousedown.stop @touchstart.stop
                            class="text-white p-2 hover:bg-white/10 rounded-full transition-colors">
                        <GIcon name="Close" :size="20" class="text-white" />
                    </button>
                </div>
            </div>


            <!-- Tap zones (invisible) -->
            <button @click.stop="prev"
                    class="absolute left-0 top-0 w-1/3 h-full opacity-0 z-10"
                    aria-label="Previous story" />
            <button @click.stop="next"
                    class="absolute right-0 top-0 w-1/3 h-full opacity-0 z-10"
                    aria-label="Next story" />
        </div>

        <!-- Nav arrows for desktop -->
        <button v-if="current > 0"
                @click="prev"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition-all hidden sm:flex">
            <GIcon name="ChevronLeft" :size="20" class="text-white" />
        </button>
        <button v-if="current < stories.length - 1"
                @click="next"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition-all hidden sm:flex">
            <GIcon name="ChevronRight" :size="20" class="text-white" />
        </button>
    </div>
</template>
