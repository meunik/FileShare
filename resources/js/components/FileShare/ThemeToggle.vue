<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Icon } from '@iconify/vue';

interface Props {
  relative?: boolean;
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'relative';
  size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  position: 'top-right',
  size: 'md'
});

const theme = ref('system');

const toggleTheme = () => {
  const themes = ['light', 'dark', 'system'];
  const currentIndex = themes.indexOf(theme.value);
  const nextIndex = (currentIndex + 1) % themes.length;
  theme.value = themes[nextIndex];
  
  localStorage.setItem('theme', theme.value);
  applyTheme();
};

const applyTheme = () => {
  const root = document.documentElement;
  
  if (theme.value === 'dark') root.classList.add('dark');
  else if (theme.value === 'light') root.classList.remove('dark');
  else { // system
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) root.classList.add('dark');
    else root.classList.remove('dark');
  }
};

onMounted(() => {
  theme.value = localStorage.getItem('theme') || 'system';
  applyTheme();

  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
  const handleSystemThemeChange = () => {
    if (theme.value === 'system') applyTheme();
  };
  
  mediaQuery.addEventListener('change', handleSystemThemeChange);
  onUnmounted(() => mediaQuery.removeEventListener('change', handleSystemThemeChange));
});

const getThemeIcon = (): string => {
  switch (theme.value) {
    case 'light': return 'ph:sun-dim-fill';
    case 'dark': return 'ph:moon-light';
    case 'system': return 'ph:monitor';
  }
  return '';
};

const getThemeTooltip = (): string => {
  switch (theme.value) {
    case 'light': return 'Tema claro';
    case 'dark': return 'Tema escuro';
    case 'system': return 'Tema do sistema';
    default: return '';
  }
};

const getPositionClasses = (): string => {
  if (props.relative) return 'relative';
  switch (props.position) {
    case 'top-right': return 'fixed top-4 right-4';
    case 'top-left': return 'fixed top-4 left-4';
    case 'bottom-right': return 'fixed bottom-4 right-4';
    case 'bottom-left': return 'fixed bottom-4 left-4';
    case 'relative': return 'relative';
    default: return 'fixed top-4 right-4';
  }
};

const getSizeClasses = (): string => {
  switch (props.size) {
    case 'sm': return 'p-1.5';
    case 'md': return 'p-2';
    case 'lg': return 'p-3';
    default: return 'p-2';
  }
};

const getIconSize = (): string => {
  switch (props.size) {
    case 'sm': return 'text-sm';
    case 'md': return 'text-base';
    case 'lg': return 'text-lg';
    default: return 'text-base';
  }
};
</script>

<template>
  <button
    @click="toggleTheme"
    :title="getThemeTooltip()"
    :class="[
      getPositionClasses(),
      getSizeClasses(),
      'rounded-lg bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-gray-200 dark:border-zinc-700 hover:bg-white dark:hover:bg-zinc-800 transition-all duration-200 shadow-sm hover:shadow-md group z-10'
    ]"
  >
    <Icon 
      :icon="getThemeIcon()" 
      :class="[
        getIconSize(),
        'text-gray-600 dark:text-gray-300 group-hover:text-gray-800 dark:group-hover:text-white transition-colors duration-200'
      ]"
    />
  </button>
</template>