<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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

const liked = ref(false);
const likesCount = ref(0);
const likeAnimating = ref(false);

const viewsCount = ref(0);
const viewersOpen = ref(false);
const viewers = ref([]);
const loadingViewers = ref(false);

watch(story, (s) => {
    liked.value = s?.is_liked ?? false;
    likesCount.value = s?.likes_count ?? 0;
    viewsCount.value = s?.views_count ?? 0;
    viewersOpen.value = false;
    viewers.value = [];
    if (s && s.user?.id !== authUser.id) {
        axios.post(route('story-views.store', s.id))
            .then(res => { viewsCount.value = res.data.views_count; });
    }
}, { immediate: true });

async function toggleLike() {
    if (likeAnimating.value) return;
    likeAnimating.value = true;
    const res = await axios.post(route('story-likes.toggle', story.value.id));
    liked.value = res.data.liked;
    likesCount.value = res.data.count;
    setTimeout(() => { likeAnimating.value = false; }, 300);
}

async function openViewers() {
    if (loadingViewers.value) return;
    viewersOpen.value = !viewersOpen.value;
    if (viewersOpen.value && viewers.value.length === 0) {
        loadingViewers.value = true;
        const res = await axios.get(route('story-views.index', story.value.id));
        viewers.value = res.data.viewers;
        viewsCount.value = res.data.views_count;
        loadingViewers.value = false;
    }
}

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


            <!-- Like button (non-owners only) -->
            <div v-if="!isOwn" class="absolute bottom-5 right-4 flex flex-col items-center gap-1 z-30"
                 @mousedown.stop @touchstart.stop>
                <button @click.stop="toggleLike"
                        class="flex flex-col items-center gap-0.5 group">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         :class="['w-7 h-7 transition-all duration-300', liked ? 'text-red-500 scale-110' : 'text-white', likeAnimating ? 'scale-125' : '']"
                         viewBox="0 0 24 24"
                         :fill="liked ? 'currentColor' : 'none'"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="text-white text-xs font-semibold drop-shadow">{{ likesCount }}</span>
                </button>
            </div>

            <!-- Views button + panel (owner only) -->
            <div v-if="isOwn" class="absolute bottom-5 left-0 right-0 px-4 z-30"
                 @mousedown.stop @touchstart.stop>

                <!-- Viewers slide-up panel -->
                <div v-if="viewersOpen"
                     class="mb-3 bg-black/70 backdrop-blur-sm rounded-2xl overflow-hidden max-h-52 overflow-y-auto">
                    <div v-if="loadingViewers" class="flex items-center justify-center py-6">
                        <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <div v-else-if="viewers.length === 0" class="py-5 text-center text-white/60 text-sm">
                        No viewers yet
                    </div>
                    <div v-for="viewer in viewers" :key="viewer.id"
                         class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 transition-colors">
                        <img :src="viewer.avatar_url" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                        <div class="min-w-0">
                            <p class="text-white text-sm font-semibold truncate">{{ viewer.name }}</p>
                            <p class="text-white/50 text-xs truncate">@{{ viewer.username }}</p>
                        </div>
                    </div>
                </div>

                <!-- Views count button -->
                <button @click.stop="openViewers"
                        class="flex items-center gap-2 text-white/90 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span class="text-sm font-semibold drop-shadow">{{ viewsCount }} {{ viewsCount === 1 ? 'view' : 'views' }}</span>
                </button>
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
