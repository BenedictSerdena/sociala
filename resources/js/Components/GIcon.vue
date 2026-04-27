<script setup>
import { computed } from 'vue';
import { getIcon } from '@/utils/icons-data.js';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: Number, default: 20 },
    filled: { type: Boolean, default: false },
});

const svg = computed(() => getIcon(props.name, props.size, props.filled));
</script>

<template>
    <span v-html="svg" style="display:inline-flex;align-items:center;line-height:1;" />
</template>
