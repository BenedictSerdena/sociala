import { ref } from 'vue';

export const toasts = ref([]);
let nextId = 0;

export function useToast() {
    function show(message, type = 'success', duration = 3500) {
        const id = ++nextId;
        toasts.value.push({ id, message, type });
        setTimeout(() => remove(id), duration);
    }

    function remove(id) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    return { show, remove };
}
