<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeToggle from '@/components/FileShare/ThemeToggle.vue';

interface Props {
  identifier: string;
  error?: string;
}

const props = defineProps<Props>();

const password = ref('');
const isValidating = ref(false);

const validatePassword = async () => {
  if (!password.value.trim()) return;

  isValidating.value = true;

  try {
    const formData = new FormData();
    formData.append('password', password.value);

    const response = await fetch(`/${props.identifier}/validate-password`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    const result = await response.json();

    if (result.success) {
      // Salva a senha e o token no sessionStorage
      sessionStorage.setItem(`page_password_${props.identifier}`, password.value);
      sessionStorage.setItem(`page_token_${props.identifier}`, result.session_token);
      
      // Redireciona para a página
      router.visit(`/${props.identifier}`);
    } else {
      // Recarrega a página com erro
      router.visit(`/${props.identifier}`, {
        data: { password: password.value },
        preserveState: false
      });
    }
  } catch (error) {
    alert('Erro ao validar senha. Tente novamente.');
  } finally {
    isValidating.value = false;
  }
};

const handleKeyPress = (event: KeyboardEvent) => {
  if (event.key === 'Enter') validatePassword();
};

const goHome = () => router.visit('/');
</script>

<template>
<Head :title="`Página protegida: ${identifier}`" />

<div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 p-6 dark:from-zinc-900 dark:to-black relative">
  <ThemeToggle />

  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <button
        @click="goHome"
        class="text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center space-x-2 hover:underline cursor-pointer mx-auto mb-4"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Voltar ao início</span>
      </button>
      
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        Página Protegida
      </h1>
      <p class="text-gray-600 dark:text-gray-400 break-all">
        {{ identifier }}
      </p>
    </div>

    <div class="bg-white dark:bg-zinc-900/40 rounded-lg shadow-xl p-8">
      <div class="text-center mb-6">
        <svg class="mx-auto h-12 w-12 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9-7a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          Esta página é protegida por senha
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Digite a senha para acessar o conteúdo
        </p>
      </div>

      <!-- Mensagem de erro -->
      <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg dark:bg-red-900/20 dark:border-red-600 dark:text-red-400 text-sm">
        {{ error }}
      </div>

      <div class="space-y-4">
        <div>
          <input
            v-model="password"
            type="password"
            placeholder="Digite a senha..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-transparent dark:border-zinc-800 dark:text-white dark:placeholder-blue-300"
            @keypress="handleKeyPress"
            :disabled="isValidating"
            autocomplete="off"
            spellcheck="false"
            autofocus
          />
        </div>

        <button
          @click="validatePassword"
          :disabled="!password.trim() || isValidating"
          class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-zinc-500 disabled:text-black disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center cursor-pointer"
        >
          <span v-if="isValidating" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
          {{ isValidating ? 'Validando...' : 'Acessar' }}
        </button>
      </div>
    </div>

    <div class="text-center mt-6 text-sm text-gray-500 dark:text-gray-400">
      Se você não tem a senha, entre em contato com quem compartilhou o link
    </div>
  </div>
</div>
</template>
