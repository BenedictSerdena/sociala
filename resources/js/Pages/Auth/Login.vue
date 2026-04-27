<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: '', password: '', remember: false });

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
                <input v-model="form.password" type="password" required autocomplete="current-password"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent focus:bg-white transition-all"
                       placeholder="••••••••" />
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
