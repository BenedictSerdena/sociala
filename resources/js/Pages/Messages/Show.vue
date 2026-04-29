<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import GIcon from '@/Components/GIcon.vue';
import CallModal from '@/Components/CallModal.vue';
import IncomingCallModal from '@/Components/IncomingCallModal.vue';

const props = defineProps({ chatUser: Object, messages: Array, pinnedMessages: Array });
const page = usePage();
const authUser = page.props.auth.user;

const messages       = ref(props.messages ?? []);
const pinnedMessages = ref(props.pinnedMessages ?? []);
const newMessage     = ref('');
const messagesContainer = ref(null);
const isTyping       = ref(false);
const sending        = ref(false);
const callOpen        = ref(false);
const callType        = ref('voice');
const callChannelName = ref(null);
const incomingCall    = ref(null); // { callType, channelName, caller }

function makeChannelName() {
    const a = Math.min(authUser.id, props.chatUser.id);
    const b = Math.max(authUser.id, props.chatUser.id);
    return `call-${a}-${b}`;
}

async function startCall(type) {
    callType.value        = type;
    callChannelName.value = makeChannelName();
    callOpen.value        = true;
    await axios.post(route('call.signal'), {
        to:        props.chatUser.id,
        type:      'incoming',
        call_type: type,
        channel:   callChannelName.value,
    }).catch(() => {});
}
async function acceptCall() {
    if (!incomingCall.value) return;
    callType.value        = incomingCall.value.callType;
    callChannelName.value = incomingCall.value.channelName;
    incomingCall.value    = null;
    callOpen.value        = true;
    await axios.post(route('call.signal'), {
        to:        props.chatUser.id,
        type:      'accepted',
        call_type: callType.value,
        channel:   callChannelName.value,
    }).catch(() => {});
}

async function declineCall() {
    if (!incomingCall.value) return;
    const ch   = incomingCall.value.channelName;
    const ct   = incomingCall.value.callType;
    incomingCall.value = null;
    await axios.post(route('call.signal'), {
        to:        props.chatUser.id,
        type:      'declined',
        call_type: ct,
        channel:   ch,
    }).catch(() => {});
}

const showPinned     = ref(false);
const imageFile      = ref(null);
const imagePreview   = ref(null);
const fileInput      = ref(null);
const contextMenu    = ref(null); // { x, y, message }
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
        if (messages.value[i].sender_id === authUser.id && messages.value[i].read_at) return i;
    }
    return -1;
});

onMounted(() => {
    scrollToBottom();
    channel = window.Echo.private(`chat.${authUser.id}`)
        .listen('MessageSent', (e) => {
            if (e.sender.id === props.chatUser.id) {
                messages.value.push({
                    id: e.id, content: e.content, image_url: e.image_url ?? null,
                    sender: e.sender, sender_id: e.sender.id,
                    created_at: e.created_at, read_at: null,
                    deleted_for_everyone: false, pinned_at: null,
                });
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
                if (m.sender_id === authUser.id && !m.read_at) m.read_at = new Date().toISOString();
            });
        });
    channel.whisper('read', {});

    window.Echo.private(`call.${authUser.id}`)
        .listen('.CallSignal', (e) => {
            if (e.type === 'incoming') {
                incomingCall.value = { callType: e.callType, channelName: e.channelName, caller: e.caller };
            } else if (e.type === 'ended' || e.type === 'declined') {
                incomingCall.value = null;
                if (callOpen.value) {
                    callOpen.value = false;
                }
            }
        });
});

onUnmounted(() => {
    if (channel) window.Echo.leave(`chat.${authUser.id}`);
    window.Echo.leave(`call.${authUser.id}`);
    clearTimeout(typingTimeout);
});

function onTyping() {
    channel?.whisper('typing', { user: authUser.id });
}

function pickImage() { fileInput.value?.click(); }
function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    imageFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
}
function clearImage() {
    imageFile.value = null;
    imagePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

async function sendMessage() {
    if (sending.value) return;
    if (!newMessage.value.trim() && !imageFile.value) return;
    sending.value = true;

    const formData = new FormData();
    if (newMessage.value.trim()) formData.append('content', newMessage.value.trim());
    if (imageFile.value) formData.append('image', imageFile.value);

    const content = newMessage.value;
    newMessage.value = '';
    clearImage();

    try {
        const res = await axios.post(route('messages.store', props.chatUser.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        messages.value.push(res.data);
        scrollToBottom();
    } catch {
        newMessage.value = content;
    } finally {
        sending.value = false;
    }
}

function openContextMenu(e, msg) {
    e.preventDefault();
    e.stopPropagation();
    const rect = e.currentTarget.getBoundingClientRect();
    contextMenu.value = { x: rect.right, y: rect.bottom, message: msg };
}
function closeContextMenu() { contextMenu.value = null; }

async function deleteForMe(msg) {
    closeContextMenu();
    await axios.delete(route('messages.deleteForMe', msg.id));
    messages.value = messages.value.filter(m => m.id !== msg.id);
    pinnedMessages.value = pinnedMessages.value.filter(m => m.id !== msg.id);
}

async function deleteForEveryone(msg) {
    closeContextMenu();
    await axios.delete(route('messages.deleteForEveryone', msg.id));
    const m = messages.value.find(m => m.id === msg.id);
    if (m) { m.deleted_for_everyone = true; m.content = null; m.image_url = null; }
    pinnedMessages.value = pinnedMessages.value.filter(m => m.id !== msg.id);
}

async function togglePin(msg) {
    closeContextMenu();
    const res = await axios.post(route('messages.pin', msg.id));
    const m = messages.value.find(m => m.id === msg.id);
    if (m) m.pinned_at = res.data.pinned ? new Date().toISOString() : null;

    if (res.data.pinned) {
        if (!pinnedMessages.value.find(p => p.id === msg.id)) pinnedMessages.value.push({ ...msg, pinned_at: new Date().toISOString() });
    } else {
        pinnedMessages.value = pinnedMessages.value.filter(p => p.id !== msg.id);
    }
}

async function scrollToBottom() {
    await nextTick();
    if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
}

function handleKeydown(e) {
    if (e.key === 'Escape') closeContextMenu();
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}
</script>

<template>
    <AppLayout>
        <CallModal :open="callOpen" :type="callType" :chat-user="chatUser" :current-user="authUser"
                   :channel-name="callChannelName" @close="callOpen = false" />
        <IncomingCallModal :call="incomingCall" @accept="acceptCall" @decline="declineCall" />

        <!-- Context menu -->
        <Teleport to="body">
            <div v-if="contextMenu" class="fixed inset-0 z-50" @click="closeContextMenu" @contextmenu.prevent="closeContextMenu">
                <div class="absolute bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden min-w-44 py-1"
                     :style="{ top: Math.min(contextMenu.y, window.innerHeight - 200) + 'px', left: Math.min(contextMenu.x, window.innerWidth - 200) + 'px' }">

                    <button @click="togglePin(contextMenu.message)"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        {{ contextMenu.message.pinned_at ? 'Unpin Message' : 'Pin Message' }}
                    </button>

                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                    <button @click="deleteForMe(contextMenu.message)"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete for Me
                    </button>

                    <button v-if="contextMenu.message.sender_id === authUser.id && !contextMenu.message.deleted_for_everyone"
                            @click="deleteForEveryone(contextMenu.message)"
                            class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 font-semibold hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        Delete for Everyone
                    </button>
                </div>
            </div>
        </Teleport>

        <div class="max-w-xl mx-auto bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm flex flex-col" style="height:calc(100vh - 120px)">

            <!-- Header -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800 rounded-t-2xl flex-shrink-0">
                <Link :href="route('messages.index')" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-500 dark:text-gray-400">
                    <GIcon name="ChevronLeft" :size="20" />
                </Link>
                <img :src="chatUser.avatar_url" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">{{ chatUser.name }}</p>
                    <p class="text-xs transition-all" :class="isTyping ? 'text-indigo-500 font-medium' : 'text-gray-400'">
                        {{ isTyping ? 'typing…' : '@' + chatUser.username }}
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <!-- Pin toggle -->
                    <button v-if="pinnedMessages.length > 0"
                            @click="showPinned = !showPinned"
                            class="p-2 rounded-xl transition-all relative"
                            :class="showPinned ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-600' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                            title="Pinned messages">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        <span class="absolute -top-0.5 -right-0.5 bg-indigo-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                            {{ pinnedMessages.length }}
                        </span>
                    </button>
                    <button @click="startCall('voice')"
                            class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all" title="Voice call">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </button>
                    <button @click="startCall('video')"
                            class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all" title="Video call">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Pinned messages panel -->
            <div v-if="showPinned && pinnedMessages.length > 0"
                 class="border-b border-indigo-100 dark:border-indigo-900 bg-indigo-50/60 dark:bg-indigo-950/30 px-4 py-3 flex-shrink-0 space-y-2 max-h-40 overflow-y-auto">
                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5 mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                    {{ pinnedMessages.length }} Pinned Message{{ pinnedMessages.length > 1 ? 's' : '' }}
                </p>
                <div v-for="p in pinnedMessages" :key="p.id"
                     class="text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-xl px-3 py-2 border border-indigo-100 dark:border-indigo-800 truncate">
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ p.sender_id === authUser.id ? 'You' : chatUser.name }}:</span>
                    {{ p.image_url && !p.content ? '📷 Photo' : p.content }}
                </div>
            </div>

            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-1 bg-gray-50/30 dark:bg-gray-800/20">
                <div v-for="(msg, i) in messages" :key="msg.id">
                    <!-- Date separator -->
                    <div v-if="i === 0 || formatDate(messages[i-1].created_at) !== formatDate(msg.created_at)"
                         class="flex justify-center my-4">
                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full font-medium">{{ formatDate(msg.created_at) }}</span>
                    </div>

                    <div class="flex mb-1" :class="msg.sender_id === authUser.id ? 'justify-end' : 'justify-start'">
                        <div class="flex items-end gap-2 max-w-[75%] group" :class="msg.sender_id === authUser.id ? 'flex-row-reverse' : 'flex-row'">
                            <img v-if="msg.sender_id !== authUser.id"
                                 :src="chatUser.avatar_url" class="w-7 h-7 rounded-full object-cover flex-shrink-0 mb-1" />
                            <div>
                                <!-- Deleted for everyone -->
                                <div v-if="msg.deleted_for_everyone"
                                     class="flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-dashed bg-transparent"
                                     :class="msg.sender_id === authUser.id
                                         ? 'border-indigo-200 dark:border-indigo-800'
                                         : 'border-gray-200 dark:border-gray-700'">
                                    <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    <span class="text-sm italic text-gray-400 dark:text-gray-500">
                                        {{ msg.sender_id === authUser.id ? 'You deleted this message' : 'This message was deleted' }}
                                    </span>
                                </div>

                                <!-- Normal message -->
                                <div v-else
                                     class="relative flex items-center gap-1"
                                     :class="msg.sender_id === authUser.id ? 'flex-row-reverse' : 'flex-row'">

                                    <!-- 3-dot menu button (shows on group hover) -->
                                    <div class="relative flex-shrink-0 self-center">
                                        <button @click.stop="openContextMenu($event, msg)"
                                                class="opacity-0 group-hover:opacity-100 p-1.5 rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-all"
                                                title="More options">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Bubble wrapper -->
                                    <div class="relative">
                                    <!-- Pin badge on bubble -->
                                    <div v-if="msg.pinned_at"
                                         class="absolute -top-2 z-10 flex items-center gap-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-full px-2 py-0.5 shadow-sm"
                                         :class="msg.sender_id === authUser.id ? 'right-2' : 'left-2'">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                        <span class="text-[10px] font-bold">Pinned</span>
                                    </div>
                                    <!-- Image -->
                                    <div v-if="msg.image_url"
                                         class="rounded-2xl overflow-hidden mb-1 shadow-sm max-w-xs"
                                         :class="[msg.sender_id === authUser.id ? 'rounded-br-sm' : 'rounded-bl-sm', msg.pinned_at ? 'mt-3' : '']">
                                        <img :src="msg.image_url" class="w-full object-cover max-h-72" @click="window.open(msg.image_url)" />
                                    </div>
                                    <!-- Text -->
                                    <div v-if="msg.content"
                                         class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed shadow-sm"
                                         :class="[
                                             msg.sender_id === authUser.id
                                                 ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-br-sm'
                                                 : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-bl-sm',
                                             msg.pinned_at && !msg.image_url ? 'mt-3' : ''
                                         ]">
                                        {{ msg.content }}
                                    </div>
                                    </div><!-- /bubble wrapper -->
                                </div><!-- /normal message flex -->

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

                <!-- Typing indicator -->
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

            <!-- Image preview strip -->
            <div v-if="imagePreview" class="px-4 pt-3 flex-shrink-0 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                <div class="relative inline-block">
                    <img :src="imagePreview" class="h-20 rounded-xl object-cover border border-gray-200 dark:border-gray-700" />
                    <button @click="clearImage"
                            class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-gray-800 text-white flex items-center justify-center hover:bg-red-500 transition-colors text-xs">✕</button>
                </div>
            </div>

            <!-- Input -->
            <div class="border-t border-gray-100 dark:border-gray-800 px-4 py-3 bg-white dark:bg-gray-900 rounded-b-2xl flex-shrink-0">
                <div class="flex gap-2 items-end">
                    <!-- Hidden file input -->
                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />

                    <!-- Image button -->
                    <button @click="pickImage"
                            class="p-2.5 rounded-xl text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>

                    <textarea v-model="newMessage"
                              @keydown="handleKeydown" @input="onTyping"
                              placeholder="Type a message…" rows="1"
                              class="flex-1 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 placeholder-gray-400 rounded-2xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white dark:focus:bg-gray-700 transition-all resize-none"
                              style="max-height:120px; overflow-y:auto" />

                    <button @click="sendMessage"
                            :disabled="(!newMessage.trim() && !imageFile) || sending"
                            class="w-10 h-10 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full flex items-center justify-center hover:from-indigo-700 hover:to-purple-700 disabled:opacity-40 transition-all shadow-md flex-shrink-0">
                        <GIcon name="Send" :size="16" class="text-white" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
