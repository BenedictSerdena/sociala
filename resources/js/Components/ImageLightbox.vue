<script setup>
import { onMounted, onUnmounted } from 'vue';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({ src: String });
const emit = defineEmits(['close']);

function onKey(e) {
    if (e.key === 'Escape') emit('close');
}

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90"
         @click.self="emit('close')">
        <button @click="emit('close')"
                class="absolute top-4 right-4 text-white p-2 rounded-full hover:bg-white hover:bg-opacity-10 transition-colors z-10">
            <GIcon name="Close" :size="24" class="text-white" />
        </button>
        <img :src="src" class="max-h-screen max-w-[95vw] object-contain rounded-lg shadow-2xl" />
    </div>
</template>
