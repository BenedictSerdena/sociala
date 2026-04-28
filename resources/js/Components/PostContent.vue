<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ content: String });

// Split content into plain text segments and #hashtag tokens
const segments = computed(() => {
    if (!props.content) return [];
    const parts = props.content.split(/(#\w+)/g);
    return parts.map(part => ({
        text: part,
        isTag: /^#\w+$/.test(part),
        tag: part.replace('#', ''),
    }));
});
</script>

<template>
    <p class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed whitespace-pre-line">
        <template v-for="(seg, i) in segments" :key="i">
            <Link v-if="seg.isTag"
                  :href="route('hashtags.show', seg.tag)"
                  class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">{{ seg.text }}</Link>
            <span v-else>{{ seg.text }}</span>
        </template>
    </p>
</template>
