<script setup>
import { toasts, useToast } from '@/composables/useToast.js';
import GIcon from '@/Components/GIcon.vue';

const { remove } = useToast();

const iconMap = {
    success: 'CheckCircle',
    error: 'X',
    info: 'Notification',
};

const colorMap = {
    success: 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900',
    error: 'bg-red-600 text-white',
    info: 'bg-indigo-600 text-white',
};
</script>

<template>
    <div class="fixed bottom-24 lg:bottom-6 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
        <TransitionGroup name="toast">
            <div v-for="toast in toasts" :key="toast.id"
                 class="flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-sm font-semibold pointer-events-auto min-w-[220px] max-w-xs"
                 :class="colorMap[toast.type] || colorMap.success">
                <GIcon :name="iconMap[toast.type] || 'CheckCircle'" :size="18" />
                <span class="flex-1">{{ toast.message }}</span>
                <button @click="remove(toast.id)" class="opacity-60 hover:opacity-100 transition-opacity ml-1">
                    <GIcon name="Close" :size="14" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active { transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateX(60px) scale(0.9); }
.toast-leave-to   { opacity: 0; transform: translateX(60px) scale(0.9); }
</style>
