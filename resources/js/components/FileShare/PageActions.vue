<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

interface Props {
  identifier: string;
  hasPassword: boolean;
  pageExpiresAt?: string;
  pageExists: boolean;
}

const props = defineProps<Props>();

const showActions = ref(false);
const showPassword = ref(false);
const pagePassword = ref('');

// Computed para verificar se deve mostrar as configurações
const shouldShowSettings = computed(() => {
  // Mostra apenas se a página tem senha
  return props.hasPassword;
});

onMounted(() => {
  // Recupera a senha do sessionStorage se existir
  const storedPassword = sessionStorage.getItem(`page_password_${props.identifier}`);
  if (storedPassword) {
    pagePassword.value = storedPassword;
  }
});

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const copyPassword = async () => {
  if (pagePassword.value) {
    try {
      await navigator.clipboard.writeText(pagePassword.value);
      alert('Senha copiada para a área de transferência!');
    } catch (err) {
      // Fallback para navegadores mais antigos
      const textArea = document.createElement('textarea');
      textArea.value = pagePassword.value;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      alert('Senha copiada para a área de transferência!');
    }
  }
};

const removePassword = async () => {
  if (!confirm('Tem certeza que deseja remover a senha desta página? Ela se tornará pública.')) return;
  
  try {
    const response = await fetch(`/${props.identifier}/password`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Content-Type': 'application/json',
      },
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Remove os dados do sessionStorage
      sessionStorage.removeItem(`page_password_${props.identifier}`);
      sessionStorage.removeItem(`page_token_${props.identifier}`);
      alert(result.message);
      router.reload();
    } else {
      alert(result.message || 'Erro ao remover senha.');
    }
  } catch (error) {
    alert('Erro ao remover senha. Tente novamente.');
  }
};

const deletePage = async () => {
  if (!confirm('Tem certeza que deseja deletar esta página? Todos os arquivos serão removidos permanentemente.')) return;
  
  try {
    const response = await fetch(`/${props.identifier}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Content-Type': 'application/json',
      },
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Remove os dados do sessionStorage
      sessionStorage.removeItem(`page_password_${props.identifier}`);
      sessionStorage.removeItem(`page_token_${props.identifier}`);
      alert(result.message);
      router.visit('/');
    } else {
      alert(result.message || 'Erro ao deletar página.');
    }
  } catch (error) {
    alert('Erro ao deletar página. Tente novamente.');
  }
};
</script>

<template>
  <div v-if="shouldShowSettings" class="bg-white dark:bg-zinc-900/60 rounded-lg shadow-xl p-6">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        Configurações da página
      </h3>
      <button
        @click="showActions = !showActions"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
        </svg>
      </button>
    </div>

    <!-- Informações da página -->
    <div class="mt-4 space-y-2">
      <div class="flex justify-between text-sm">
        <span class="text-gray-500 dark:text-gray-400">Status:</span>
        <span class="text-gray-900 dark:text-white">
          {{ hasPassword ? 'Protegida por senha' : 'Pública' }}
        </span>
      </div>
      
      <div v-if="pageExpiresAt" class="flex justify-between text-sm">
        <span class="text-gray-500 dark:text-gray-400">Expira em:</span>
        <span class="text-gray-900 dark:text-white">{{ pageExpiresAt }}</span>
      </div>

      <!-- Exibir senha se disponível -->
      <div v-if="hasPassword" class="space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-gray-500 dark:text-gray-400">Senha:</span>
          <div class="flex items-center space-x-2">
            <span class="text-gray-900 dark:text-white font-mono text-xs">
              {{ showPassword ? (pagePassword || 'Não disponível') : '•'.repeat(pagePassword?.length || 8) }}
            </span>
            <button
              @click="togglePasswordVisibility"
              class="text-blue-600 hover:text-blue-700 dark:text-blue-400"
              :title="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
            >
              <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
            <button
              @click="copyPassword"
              class="text-blue-600 hover:text-blue-700 dark:text-blue-400"
              title="Copiar senha"
              :disabled="!pagePassword"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Ações -->
    <div v-if="showActions" class="mt-6 space-y-2 border-t pt-4 dark:border-gray-600">
      <button
        v-if="hasPassword"
        @click="removePassword"
        class="w-full px-4 py-2 text-sm bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-300 dark:hover:bg-yellow-900/40 transition-colors cursor-pointer"
      >
        Remover senha (tornar pública)
      </button>
      
      <button
        @click="deletePage"
        class="w-full px-4 py-2 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40 transition-colors cursor-pointer"
      >
        Deletar página permanentemente
      </button>
    </div>
  </div>
</template>
