<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    chatUser: Object,
    messages: Array,
});

const page = usePage();
const authUser = page.props.auth.user;
const messages = ref(props.messages);
const newMessage = ref('');
const messagesContainer = ref(null);
let channel = null;

onMounted(() => {
    scrollToBottom();

    channel = window.Echo.private(`chat.${authUser.id}`)
        .listen('MessageSent', (e) => {
            if (e.sender.id === props.chatUser.id) {
                messages.value.push({
                    id: e.id,
                    content: e.content,
                    sender: e.sender,
                    sender_id: e.sender.id,
                    created_at: e.created_at,
                });
                scrollToBottom();
            }
        });
});

onUnmounted(() => {
    if (channel) window.Echo.leave(`chat.${authUser.id}`);
});

async function sendMessage() {
    if (!newMessage.value.trim()) return;
    const content = newMessage.value;
    newMessage.value = '';

    const res = await axios.post(route('messages.store', props.chatUser.id), { content });
    messages.value.push(res.data);
    scrollToBottom();
}

async function scrollToBottom() {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col h-[calc(100vh-140px)]">
            <!-- Header -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                <Link :href="route('messages.index')" class="text-gray-400 hover:text-gray-600">←</Link>
                <img :src="chatUser.avatar_url" class="w-9 h-9 rounded-full object-cover" />
                <div>
                    <p class="font-semibold text-gray-900 text-sm">{{ chatUser.name }}</p>
                    <p class="text-gray-400 text-xs">@{{ chatUser.username }}</p>
                </div>
            </div>

            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-3">
                <div v-for="msg in messages" :key="msg.id"
                     class="flex"
                     :class="msg.sender_id === authUser.id ? 'justify-end' : 'justify-start'">
                    <div class="max-w-xs px-4 py-2 rounded-2xl text-sm"
                         :class="msg.sender_id === authUser.id
                             ? 'bg-indigo-600 text-white rounded-br-sm'
                             : 'bg-gray-100 text-gray-800 rounded-bl-sm'">
                        {{ msg.content }}
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="border-t border-gray-100 px-4 py-3 flex gap-2">
                <input v-model="newMessage" @keyup.enter="sendMessage"
                       placeholder="Type a message…"
                       class="flex-1 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                <button @click="sendMessage"
                        class="bg-indigo-600 text-white px-5 py-2 rounded-full text-sm hover:bg-indigo-700">
                    Send
                </button>
            </div>
        </div>
    </AppLayout>
</template>
