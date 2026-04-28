<script setup>
defineProps({
    open:        { type: Boolean, default: false },
    title:       { type: String,  default: 'Are you sure?' },
    message:     { type: String,  default: '' },
    confirmText: { type: String,  default: 'Confirm' },
    cancelText:  { type: String,  default: 'Cancel' },
    danger:      { type: Boolean, default: false },
});
defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('cancel')" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="open" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <!-- Icon -->
                        <div class="flex items-center justify-center mb-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                 :class="danger ? 'bg-red-100' : 'bg-indigo-100'">
                                <svg v-if="danger" class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                </svg>
                                <svg v-else class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-base font-bold text-slate-800 text-center mb-1">{{ title }}</h3>
                        <p v-if="message" class="text-sm text-slate-500 text-center mb-6">{{ message }}</p>
                        <div v-else class="mb-6" />

                        <div class="flex gap-3">
                            <button @click="$emit('cancel')"
                                    class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                                {{ cancelText }}
                            </button>
                            <button @click="$emit('confirm')"
                                    class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                                    :class="danger
                                        ? 'bg-red-500 text-white hover:bg-red-600'
                                        : 'bg-indigo-600 text-white hover:bg-indigo-700'">
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>