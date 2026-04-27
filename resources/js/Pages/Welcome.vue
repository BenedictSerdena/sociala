<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome to SocialApp" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex flex-col">

        <!-- Nav -->
        <nav class="px-6 py-4 flex items-center justify-between max-w-6xl mx-auto w-full">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-indigo-600">SocialApp</span>
            </div>
            <div class="flex items-center gap-3" v-if="canLogin">
                <Link :href="route('login')"
                      class="text-sm font-medium text-gray-600 hover:text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition">
                    Log in
                </Link>
                <Link v-if="canRegister" :href="route('register')"
                      class="text-sm font-medium text-white bg-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-700 transition shadow-sm">
                    Get Started
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <main class="flex-1 flex items-center justify-center px-6 py-16">
            <div class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 text-sm font-medium px-4 py-1.5 rounded-full mb-6">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                    Real-time with Laravel Reverb
                </div>

                <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                    Connect with the<br>
                    <span class="text-indigo-600">people that matter</span>
                </h1>

                <p class="text-lg text-gray-500 mb-10 max-w-xl mx-auto">
                    Share posts, follow friends, send direct messages, and get notified in real-time — all in one place.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link v-if="canRegister" :href="route('register')"
                          class="px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition shadow-md text-lg">
                        Create your account
                    </Link>
                    <Link v-if="canLogin" :href="route('login')"
                          class="px-8 py-3 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition border border-indigo-200 text-lg">
                        Sign in
                    </Link>
                </div>

                <!-- Feature pills -->
                <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col items-center gap-2">
                        <span class="text-2xl">📝</span>
                        <span class="font-medium">Posts & Images</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col items-center gap-2">
                        <span class="text-2xl">❤️</span>
                        <span class="font-medium">Likes & Comments</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col items-center gap-2">
                        <span class="text-2xl">💬</span>
                        <span class="font-medium">Direct Messages</span>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex flex-col items-center gap-2">
                        <span class="text-2xl">🔔</span>
                        <span class="font-medium">Live Notifications</span>
                    </div>
                </div>
            </div>
        </main>

        <footer class="text-center text-xs text-gray-400 py-6">
            Built with Laravel 12 · Inertia.js · Vue 3 · Reverb
        </footer>
    </div>
</template>
