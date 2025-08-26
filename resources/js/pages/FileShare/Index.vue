<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeToggle from '@/components/FileShare/ThemeToggle.vue';

const identifier = ref('');
const isCreating = ref(false);

const createShare = () => {
  if (!identifier.value.trim()) return;
  
  if (identifier.value.length > 255) {
    alert('O identificador não pode ter mais de 255 caracteres.');
    return;
  }

  isCreating.value = true;

  router.visit(`/${identifier.value.trim()}`, {
    onFinish: () => { isCreating.value = false }
  });
};

const handleKeyPress = (event: KeyboardEvent) => {
  if (event.key === 'Enter') createShare();
};
</script>

<template>
<Head title="Compartilhamento de Arquivos" />

<div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 p-6 dark:from-zinc-900 dark:to-black relative">
  <ThemeToggle />

  <div class="w-full max-w-md">

    <div class="text-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        FileShare
      </h1>
    </div>

    <div class="bg-white dark:bg-zinc-900/40 rounded-lg shadow-xl p-8">
      
      <div class="space-y-4">
        <div>
          <input
            id="identifier"
            v-model="identifier"
            type="text"
            maxlength="255"
            placeholder="Digite um nome único para sua página..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-transparent dark:border-zinc-800 dark:text-white dark:placeholder-blue-300"
            @keypress="handleKeyPress"
            :disabled="isCreating"
          />
        </div>

        <button
          @click="createShare"
          :disabled="!identifier.trim() || isCreating"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-zinc-500 disabled:text-black disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center cursor-pointer"
        >
          <span v-if="isCreating" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
          {{ isCreating ? 'Criando...' : 'Criar Página' }}
        </button>
      </div>
    </div>

    <div class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
      Compartilhe arquivos de forma rápida e segura
    </div>

    <!-- <div class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
      Inspirado no <a href="https://dontpad.com" target="_blank" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">dontpad.com</a>
    </div> -->
  </div>
</div>
</template>