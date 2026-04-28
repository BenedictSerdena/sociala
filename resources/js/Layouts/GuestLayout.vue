<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-[#FAFAFA] flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-violet-500 via-purple-500 to-pink-500 rounded-2xl mb-4 shadow-xl shadow-purple-200">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <circle cx="9" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="15" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-black bg-gradient-to-r from-violet-600 to-pink-500 bg-clip-text text-transparent">Sociala</h1>
                <p class="text-gray-400 text-sm mt-1">Connect with people that matter</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                <slot />
            </div>

            <p class="text-center text-xs text-gray-400 mt-6">
                © 2025 Sociala · Built with Laravel & Vue
            </p>
        </div>
    </div>
</template>
