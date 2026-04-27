<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({ chatUser: Object, messages: Array });
const page = usePage();
const authUser = page.props.auth.user;
const messages = ref(props.messages);
const newMessage = ref('');
const messagesContainer = ref(null);
let channel = null;

function formatTime(date) {
    return new Date(date).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

function formatDate(date) {
    const d = new Date(date);
    const today = new Date();
    if (d.toDateString() === today.toDateString()) return 'Today';
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

onMounted(() => {
    scrollToBottom();
    channel = window.Echo.private(`chat.${authUser.id}`)
        .listen('MessageSent', (e) => {
            if (e.sender.id === props.chatUser.id) {
                messages.value.push({ id: e.id, content: e.content, sender: e.sender, sender_id: e.sender.id, created_at: e.created_at });
                scrollToBottom();
            }
        });
});

onUnmounted(() => { if (channel) window.Echo.leave(`chat.${authUser.id}`); });

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
    if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
}
</script>

<template>
    <AppLayout>
        <div class="max-w-xl mx-auto bg-white rounded-2xl border border-gray-200/80 shadow-sm flex flex-col" style="height:calc(100vh - 120px)">

            <!-- Header -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 rounded-t-2xl">
                <Link :href="route('messages.index')" class="p-1.5 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
                    <GIcon name="ChevronLeft" :size="20" />
                </Link>
                <div class="relative">
                    <img :src="chatUser.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100" />
                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                </div>
                <div>
                    <p class="font-bold text-gray-900 text-sm">{{ chatUser.name }}</p>
                    <p class="text-green-500 text-xs font-semibold">Online</p>
                </div>
            </div>

            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-1 bg-gray-50/30">
                <div v-for="(msg, i) in messages" :key="msg.id">
                    <div v-if="i === 0 || formatDate(messages[i-1].created_at) !== formatDate(msg.created_at)"
                         class="flex justify-center my-4">
                        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full font-medium">{{ formatDate(msg.created_at) }}</span>
                    </div>

                    <div class="flex mb-1" :class="msg.sender_id === authUser.id ? 'justify-end' : 'justify-start'">
                        <div class="flex items-end gap-2 max-w-[75%]" :class="msg.sender_id === authUser.id ? 'flex-row-reverse' : 'flex-row'">
                            <img v-if="msg.sender_id !== authUser.id"
                                 :src="chatUser.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0 mb-1" />
                            <div>
                                <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm"
                                     :class="msg.sender_id === authUser.id
                                         ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-br-sm'
                                         : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm'">
                                    {{ msg.content }}
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 font-medium" :class="msg.sender_id === authUser.id ? 'text-right' : 'text-left'">
                                    {{ formatTime(msg.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full py-16 text-center">
                    <img :src="chatUser.avatar_url" class="w-16 h-16 rounded-full object-cover mb-3 ring-4 ring-indigo-100" />
                    <p class="font-bold text-gray-800">{{ chatUser.name }}</p>
                    <p class="text-gray-400 text-sm mt-1">Send a message to start the conversation</p>
                </div>
            </div>

            <!-- Input -->
            <div class="border-t border-gray-100 px-4 py-3 bg-white rounded-b-2xl">
                <div class="flex gap-2 items-center">
                    <input v-model="newMessage" @keyup.enter="sendMessage"
                           placeholder="Type a message…"
                           class="flex-1 border border-gray-200 bg-gray-50 rounded-full px-5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition-all" />
                    <button @click="sendMessage" :disabled="!newMessage.trim()"
                            class="w-10 h-10 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full flex items-center justify-center hover:from-indigo-700 hover:to-purple-700 disabled:opacity-40 transition-all shadow-md flex-shrink-0">
                        <GIcon name="Send" :size="16" class="text-white" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
