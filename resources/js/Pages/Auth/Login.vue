<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: '', password: '', remember: false });
const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), { onFinish: () => form.reset('password') });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-5 text-sm text-green-600 bg-green-50 px-4 py-3 rounded-xl">{{ status }}</div>

        <h2 class="text-xl font-bold text-gray-900 mb-6">Welcome back</h2>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input v-model="form.email" type="email" required autofocus autocomplete="username"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent focus:bg-white transition-all"
                       placeholder="you@example.com" />
                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1.5">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-sm font-medium text-gray-700">Password</label>
                    <Link v-if="canResetPassword" :href="route('password.request')"
                          class="text-xs text-indigo-600 hover:text-indigo-700">Forgot password?</Link>
                </div>
                <div class="relative">
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password"
                           class="w-full px-4 py-3 pr-11 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent focus:bg-white transition-all"
                           placeholder="••••••••" />
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg v-if="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1.5">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.remember" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-400" />
                <span class="text-sm text-gray-600">Remember me</span>
            </label>

            <button type="submit" :disabled="form.processing"
                    class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed mt-2">
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Don't have an account?
            <Link :href="route('register')" class="text-indigo-600 font-semibold hover:text-indigo-700">Sign up</Link>
        </p>
    </GuestLayout>
</template>
