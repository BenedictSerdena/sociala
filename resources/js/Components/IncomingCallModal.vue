<script setup>
import { ref, watch, onUnmounted } from 'vue';

const props = defineProps({
    call: Object, // { callType, channelName, caller }
});

const emit = defineEmits(['accept', 'decline']);

// Ringtone
let audio = null;
watch(() => props.call, (val) => {
    if (val) {
        try {
            audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAA...');
            audio.loop = true;
            // We'll just use a silent fallback since we can't embed a real ringtone here
        } catch {}
    } else {
        audio?.pause();
        audio = null;
    }
}, { immediate: true });

onUnmounted(() => { audio?.pause(); });
</script>

<template>
    <Teleport to="body">
        <div v-if="call"
             class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-sm bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden">
                <!-- Gradient top -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 px-6 pt-8 pb-10 text-center">
                    <div class="relative inline-block mb-4">
                        <div class="absolute inset-0 rounded-full bg-white/20 animate-ping scale-110"></div>
                        <img :src="call.caller.avatar_url" class="w-20 h-20 rounded-full object-cover ring-4 ring-white/40 relative z-10" />
                    </div>
                    <p class="text-white font-bold text-xl">{{ call.caller.name }}</p>
                    <p class="text-indigo-200 text-sm mt-1">
                        Incoming {{ call.callType === 'video' ? 'video' : 'voice' }} call…
                    </p>
                    <div class="flex items-center justify-center gap-1.5 mt-2">
                        <svg v-if="call.callType === 'video'" class="w-4 h-4 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        </svg>
                        <svg v-else class="w-4 h-4 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span class="text-indigo-200 text-xs font-medium capitalize">{{ call.callType }} Call</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex divide-x divide-gray-100 dark:divide-gray-800">
                    <button @click="emit('decline')"
                            class="flex-1 flex flex-col items-center gap-2 py-5 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors group">
                        <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center group-hover:bg-red-200 transition-colors">
                            <svg class="w-6 h-6 text-red-600 rotate-[135deg]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-red-600">Decline</span>
                    </button>
                    <button @click="emit('accept')"
                            class="flex-1 flex flex-col items-center gap-2 py-5 hover:bg-green-50 dark:hover:bg-green-950/30 transition-colors group">
                        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center group-hover:bg-green-200 transition-colors">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-green-600">Accept</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
