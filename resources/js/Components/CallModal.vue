<script setup>
import { ref, watch, onUnmounted } from 'vue';
import AgoraRTC from 'agora-rtc-sdk-ng';

const props = defineProps({
    open:        Boolean,
    type:        { type: String, default: 'voice' },
    chatUser:    Object,
    currentUser: Object,
    channelName: { type: String, default: null },
});

const emit = defineEmits(['close']);

// ── State ──────────────────────────────────────────────
const callState  = ref('calling'); // calling | connected | ended | declined
const muted      = ref(false);
const cameraOff  = ref(false);
const speaker    = ref(true);
const duration   = ref(0);
const statusText = ref('Calling…');
let durationTimer = null;

// ── Agora ───────────────────────────────────────────────
let client         = null;
let localAudioTrack = null;
let localVideoTrack = null;

async function startCall() {
    if (!props.channelName) return;

    try {
        // Get token from backend
        const res = await axios.post(route('call.token'), { channel: props.channelName });
        const { token, uid } = res.data;
        const app_id = import.meta.env.VITE_AGORA_APP_ID;

        client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

        // Handle remote user publishing streams
        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'video') {
                callState.value = 'connected';
                statusText.value = 'Connected';
                startTimer();
                user.videoTrack?.play('agora-remote-video');
            }
            if (mediaType === 'audio') {
                user.audioTrack?.play();
                if (callState.value !== 'connected') {
                    callState.value = 'connected';
                    statusText.value = 'Connected';
                    startTimer();
                }
            }
        });

        client.on('user-unpublished', () => {
            // remote user muted/disabled — UI already handles this
        });

        client.on('user-left', () => {
            endCall(true);
        });

        await client.join(app_id, props.channelName, token, uid);

        // Publish local tracks
        if (props.type === 'video') {
            [localAudioTrack, localVideoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
            localVideoTrack.play('agora-local-video');
        } else {
            localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
        }

        await client.publish(localAudioTrack ? [localAudioTrack] : []);
        if (localVideoTrack) await client.publish([localVideoTrack]);

    } catch (err) {
        console.error('Agora error:', err);
        callState.value = 'ended';
        statusText.value = 'Connection failed';
        setTimeout(() => emit('close'), 2000);
    }
}

async function cleanupAgora() {
    clearInterval(durationTimer);
    durationTimer = null;
    localAudioTrack?.close();
    localVideoTrack?.close();
    localAudioTrack = null;
    localVideoTrack = null;
    if (client) {
        await client.leave().catch(() => {});
        client = null;
    }
}

function startTimer() {
    if (durationTimer) return;
    durationTimer = setInterval(() => duration.value++, 1000);
}

watch(() => props.open, async (val) => {
    if (val) {
        callState.value = 'calling';
        statusText.value = 'Calling…';
        muted.value = false;
        cameraOff.value = false;
        duration.value = 0;
        await startCall();
    } else {
        await cleanupAgora();
    }
});

onUnmounted(cleanupAgora);

// ── Controls ────────────────────────────────────────────
async function toggleMute() {
    muted.value = !muted.value;
    if (localAudioTrack) await localAudioTrack.setMuted(muted.value);
}

async function toggleCamera() {
    cameraOff.value = !cameraOff.value;
    if (localVideoTrack) await localVideoTrack.setMuted(cameraOff.value);
}

async function endCall(remoteEnded = false) {
    // Signal the other side if we're the one ending
    if (!remoteEnded && props.chatUser) {
        await axios.post(route('call.signal'), {
            to:         props.chatUser.id,
            type:       'ended',
            call_type:  props.type,
            channel:    props.channelName,
        }).catch(() => {});
    }
    await cleanupAgora();
    callState.value = 'ended';
    statusText.value = remoteEnded ? 'Call ended' : 'Call ended';
    setTimeout(() => emit('close'), 1500);
}

function formatDuration(s) {
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open"
             class="fixed inset-0 z-[100] flex items-center justify-center"
             :class="type === 'video' ? 'bg-black' : 'bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900'">

            <!-- VIDEO layout -->
            <template v-if="type === 'video'">
                <div class="absolute inset-0">
                    <div v-if="callState === 'connected'"
                         id="agora-remote-video" class="w-full h-full bg-slate-800"></div>
                    <div v-else class="w-full h-full bg-slate-900 flex flex-col items-center justify-center gap-4">
                        <div class="relative">
                            <div v-if="callState === 'calling'" class="absolute inset-0 rounded-full bg-indigo-500/20 animate-ping scale-150"></div>
                            <img :src="chatUser?.avatar_url" class="w-28 h-28 rounded-full object-cover ring-4 ring-white/20 relative z-10" />
                        </div>
                        <p class="text-white text-xl font-bold">{{ chatUser?.name }}</p>
                        <p class="text-slate-400 text-sm">{{ statusText }}</p>
                    </div>
                </div>

                <!-- Local PiP -->
                <div class="absolute top-5 right-5 w-32 h-44 rounded-2xl overflow-hidden bg-slate-700 ring-2 ring-white/20 shadow-2xl z-10">
                    <div id="agora-local-video" class="w-full h-full"></div>
                    <div v-if="cameraOff || callState !== 'connected'"
                         class="absolute inset-0 bg-slate-800 flex items-center justify-center">
                        <img :src="currentUser?.avatar_url" class="w-12 h-12 rounded-full object-cover" />
                    </div>
                </div>

                <!-- Top bar -->
                <div class="absolute top-5 left-5 z-10">
                    <div class="bg-black/50 backdrop-blur-sm rounded-2xl px-4 py-2 flex items-center gap-2">
                        <div v-if="callState === 'connected'" class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                        <p class="text-white text-sm font-semibold">
                            {{ callState === 'connected' ? formatDuration(duration) : statusText }}
                        </p>
                    </div>
                </div>
            </template>

            <!-- VOICE layout -->
            <template v-else>
                <div class="flex flex-col items-center gap-6 px-8 w-full max-w-sm">
                    <div class="relative">
                        <div v-if="callState === 'calling'" class="absolute inset-0 rounded-full bg-indigo-500/20 animate-ping scale-150"></div>
                        <div v-if="callState === 'calling'" class="absolute inset-0 rounded-full bg-indigo-500/10 animate-ping scale-125" style="animation-delay:0.3s"></div>
                        <img :src="chatUser?.avatar_url" class="w-28 h-28 rounded-full object-cover ring-4 ring-white/20 relative z-10 shadow-2xl" />
                    </div>
                    <div class="text-center">
                        <p class="text-white text-2xl font-bold">{{ chatUser?.name }}</p>
                        <p class="text-sm mt-1 font-medium"
                           :class="callState === 'connected' ? 'text-green-400' : 'text-slate-300'">
                            {{ callState === 'connected' ? formatDuration(duration) : statusText }}
                        </p>
                    </div>
                </div>
            </template>

            <!-- Controls -->
            <div class="absolute bottom-10 left-0 right-0 flex items-center justify-center gap-5 z-10">

                <button @click="toggleMute" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center transition-all shadow-lg"
                         :class="muted ? 'bg-white text-slate-900' : 'bg-white/15 text-white hover:bg-white/25'">
                        <svg v-if="muted" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>
                    <span class="text-xs text-white/70">{{ muted ? 'Unmute' : 'Mute' }}</span>
                </button>

                <button v-if="type === 'video'" @click="toggleCamera" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center transition-all shadow-lg"
                         :class="cameraOff ? 'bg-white text-slate-900' : 'bg-white/15 text-white hover:bg-white/25'">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        </svg>
                    </div>
                    <span class="text-xs text-white/70">{{ cameraOff ? 'Show Cam' : 'Hide Cam' }}</span>
                </button>

                <button v-if="type === 'voice'" @click="speaker = !speaker" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center transition-all shadow-lg"
                         :class="!speaker ? 'bg-white text-slate-900' : 'bg-white/15 text-white hover:bg-white/25'">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m-3.536-9.536a5 5 0 000 7.072" />
                        </svg>
                    </div>
                    <span class="text-xs text-white/70">{{ speaker ? 'Speaker' : 'Earpiece' }}</span>
                </button>

                <button @click="endCall(false)" class="flex flex-col items-center gap-2">
                    <div class="w-16 h-16 rounded-full bg-red-500 hover:bg-red-600 flex items-center justify-center transition-all shadow-lg shadow-red-500/40">
                        <svg class="w-7 h-7 text-white rotate-[135deg]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <span class="text-xs text-white/70">End</span>
                </button>
            </div>
        </div>
    </Teleport>
</template>
