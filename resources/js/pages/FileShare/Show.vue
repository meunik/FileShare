<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import ThemeToggle from '@/components/FileShare/ThemeToggle.vue';
import ExistingFiles from '@/components/FileShare/ExistingFiles.vue';
import FileUpload from '@/components/FileShare/FileUpload.vue';
import PageActions from '@/components/FileShare/PageActions.vue';
import { Icon } from '@iconify/vue';

interface ExistingFile {
  id: number;
  original_name: string;
  size: string;
  expires_at: string;
}

interface Props {
  identifier: string;
  existingFiles: ExistingFile[];
  maxFiles: number;
  maxFileSize: number;
  hasPassword: boolean;
  pageExpiresAt?: string;
  pageExists: boolean;
}

const props = defineProps<Props>();

// Computed properties
const canAddMoreFiles = computed(() => props.existingFiles.length < props.maxFiles);

onMounted(() => {
  // Se a página tem senha, configura interceptor para incluir o token nos requests
  if (props.hasPassword) {
    const token = sessionStorage.getItem(`page_token_${props.identifier}`);
    if (token) {
      // Configura o header global para requests dessa página
      const originalFetch = window.fetch;
      window.fetch = function(input, init = {}) {
        // Só adiciona o header para requests relacionados a esta página
        if (typeof input === 'string' && input.includes(props.identifier)) {
          init.headers = {
            ...init.headers,
            'X-Session-Token': token
          };
        }
        return originalFetch.call(this, input, init);
      };
    }
  }
});

// Função para voltar à página inicial
const goHome = () => router.visit('/');
</script>

<template>
<Head :title="`Compartilhamento: ${identifier}`" />

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-zinc-900 dark:to-black relative">
  <!-- Header -->
  <div class="backdrop-blur-sm">
    <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2 sm:space-x-4">
        <button
          @click="goHome"
          class="text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center sm:space-x-2 hover:underline cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:block">Voltar</span>
        </button>
        <div class="text-sm text-gray-500 dark:text-gray-400">|</div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white break-all line-clamp-1">{{ identifier }}</h1>
        <div v-if="hasPassword" class="flex items-center">
          <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-2 0h4m-2 0h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
      <div class="text-sm text-gray-500 dark:text-gray-400 text-nowrap whitespace-nowrap flex items-center space-x-1 px-2">
        <span>{{ existingFiles.length }}/{{ maxFiles }}</span>
        <span class="hidden sm:block">arquivos</span>
        <Icon class="sm:hidden" icon="ph:file" />
      </div>
      <ThemeToggle relative />
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-6 py-8 space-y-8">
    <!-- Arquivos existentes -->
    <ExistingFiles :files="existingFiles" />

    <!-- Formulário de upload -->
    <FileUpload 
      :identifier="identifier"
      :max-file-size="maxFileSize"
      :can-add-more-files="canAddMoreFiles"
      :max-files="maxFiles"
    />

    <!-- Ações da página -->
    <PageActions 
      :identifier="identifier"
      :has-password="hasPassword"
      :page-expires-at="pageExpiresAt"
      :page-exists="pageExists"
    />

    <!-- Informações sobre o funcionamento -->
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
      <h3 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
        Como funciona:
      </h3>
      <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
        <li>• Crie uma página única com um nome personalizado</li>
        <li>• Compartilhe até 2 arquivos por página (máx. 50GB cada)</li>
        <li>• Defina o tempo de expiração (máx. 24 horas)</li>
        <li>• Opcionalmente proteja com senha</li>
        <li>• Compartilhe o link com quem quiser</li>
      </ul>
    </div>
  </div>
</div>
</template>
