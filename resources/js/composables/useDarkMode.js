import { ref, watch, onMounted } from 'vue';

const dark = ref(false);

export function useDarkMode() {
    onMounted(() => {
        dark.value = localStorage.getItem('theme') === 'dark';
        applyTheme(dark.value);
    });

    function applyTheme(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
    }

    function toggle() {
        dark.value = !dark.value;
        localStorage.setItem('theme', dark.value ? 'dark' : 'light');
        applyTheme(dark.value);
    }

    return { dark, toggle };
}
