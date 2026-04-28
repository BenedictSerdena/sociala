<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';

const props = defineProps({ chatUser: Object, messages: Array });
const page = usePage();
const authUser = page.props.auth.user;
const messages = ref(props.messages);
const newMessage = ref('');
const messagesContainer = ref(null);
const isTyping = ref(false);
let channel = null;
let typingTimeout = null;

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

const lastReadIndex = computed(() => {
    for (let i = messages.value.length - 1; i >= 0; i--) {
        if (messages.value[i].sender_id === authUser.id && messages.value[i].read_at) {
            return i;
        }
    }
    return -1;
});

onMounted(() => {
    scrollToBottom();
    channel = window.Echo.private(`chat.${authUser.id}`)
        .listen('MessageSent', (e) => {
            if (e.sender.id === props.chatUser.id) {
                messages.value.push({ id: e.id, content: e.content, sender: e.sender, sender_id: e.sender.id, created_at: e.created_at, read_at: null });
                axios.post(route('messages.read', props.chatUser.id));
                scrollToBottom();
            }
        })
        .listenForWhisper('typing', () => {
            isTyping.value = true;
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => { isTyping.value = false; }, 2500);
        })
        .listenForWhisper('read', () => {
            messages.value.forEach(m => {
                if (m.sender_id === authUser.id && !m.read_at) {
                    m.read_at = new Date().toISOString();
                }
            });
        });

    channel.whisper('read', {});
});

onUnmounted(() => {
    if (channel) window.Echo.leave(`chat.${authUser.id}`);
    clearTimeout(typingTimeout);
});

function onTyping() {
    channel?.whisper('typing', { user: authUser.id });
}

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
        <div class="max-w-xl mx-auto bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm flex flex-col" style="height:calc(100vh - 120px)">

            <!-- Header -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800 rounded-t-2xl">
                <Link :href="route('messages.index')" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-500 dark:text-gray-400">
                    <GIcon name="ChevronLeft" :size="20" />
                </Link>
                <img :src="chatUser.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700" />
                <div>
                    <p class="font-bold text-gray-900 dark:text-white text-sm">{{ chatUser.name }}</p>
                    <p class="text-xs transition-all" :class="isTyping ? 'text-indigo-500 font-medium' : 'text-gray-400'">
                        {{ isTyping ? 'typing…' : '@' + chatUser.username }}
                    </p>
                </div>
            </div>

            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-1 bg-gray-50/30 dark:bg-gray-800/20">
                <div v-for="(msg, i) in messages" :key="msg.id">
                    <div v-if="i === 0 || formatDate(messages[i-1].created_at) !== formatDate(msg.created_at)"
                         class="flex justify-center my-4">
                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full font-medium">{{ formatDate(msg.created_at) }}</span>
                    </div>

                    <div class="flex mb-1" :class="msg.sender_id === authUser.id ? 'justify-end' : 'justify-start'">
                        <div class="flex items-end gap-2 max-w-[75%]" :class="msg.sender_id === authUser.id ? 'flex-row-reverse' : 'flex-row'">
                            <img v-if="msg.sender_id !== authUser.id"
                                 :src="chatUser.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0 mb-1" />
                            <div>
                                <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm"
                                     :class="msg.sender_id === authUser.id
                                         ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-br-sm'
                                         : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-bl-sm'">
                                    {{ msg.content }}
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 font-medium" :class="msg.sender_id === authUser.id ? 'text-right' : 'text-left'">
                                    {{ formatTime(msg.created_at) }}
                                </p>
                                <p v-if="msg.sender_id === authUser.id && i === lastReadIndex"
                                   class="text-[10px] text-indigo-400 font-semibold text-right mt-0.5 flex items-center justify-end gap-1">
                                    <img :src="chatUser.avatar_url" class="w-3 h-3 rounded-full object-cover" />
                                    Seen
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Typing indicator bubble -->
                <div v-if="isTyping" class="flex justify-start mb-2">
                    <div class="flex items-end gap-2">
                        <img :src="chatUser.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0" />
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm flex gap-1 items-center">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                        </div>
                    </div>
                </div>

                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full py-16 text-center">
                    <img :src="chatUser.avatar_url" class="w-16 h-16 rounded-full object-cover mb-3 ring-4 ring-indigo-100 dark:ring-indigo-900" />
                    <p class="font-bold text-gray-800 dark:text-gray-200">{{ chatUser.name }}</p>
                    <p class="text-gray-400 text-sm mt-1">Send a message to start the conversation</p>
                </div>
            </div>

            <!-- Input -->
            <div class="border-t border-gray-100 dark:border-gray-800 px-4 py-3 bg-white dark:bg-gray-900 rounded-b-2xl">
                <div class="flex gap-2 items-center">
                    <input v-model="newMessage" @keyup.enter="sendMessage" @input="onTyping"
                           placeholder="Type a message…"
                           class="flex-1 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 placeholder-gray-400 rounded-full px-5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white dark:focus:bg-gray-700 transition-all" />
                    <button @click="sendMessage" :disabled="!newMessage.trim()"
                            class="w-10 h-10 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full flex items-center justify-center hover:from-indigo-700 hover:to-purple-700 disabled:opacity-40 transition-all shadow-md flex-shrink-0">
                        <GIcon name="Send" :size="16" class="text-white" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
