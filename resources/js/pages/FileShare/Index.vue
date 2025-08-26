<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ThemeToggle from '@/components/FileShare/ThemeToggle.vue';

const identifier = ref('');
const password = ref('');
const duration = ref<number>(1);
const unit = ref<'second' | 'minute' | 'hour'>('hour');
const isCreating = ref(false);
const message = ref('');

const hasPassword = computed(() => password.value.trim().length > 0);

const createShare = async () => {
  if (!identifier.value.trim()) return;
  
  if (identifier.value.length > 255) {
    alert('O identificador não pode ter mais de 255 caracteres.');
    return;
  }

  if (hasPassword.value && (!duration.value || duration.value < 1)) {
    alert('Por favor, defina um tempo de duração válido.');
    return;
  }

  isCreating.value = true;
  message.value = '';

  try {
    const formData = new FormData();
    formData.append('identifier', identifier.value.trim());
    
    if (hasPassword.value) {
      formData.append('password', password.value);
      formData.append('duration', duration.value.toString());
      formData.append('unit', unit.value);
    }

    const response = await fetch('/create', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    const result = await response.json();

    if (result.success) {
      // Se a página foi criada com senha, salva no sessionStorage
      if (hasPassword.value) {
        sessionStorage.setItem(`page_password_${identifier.value}`, password.value);
      }
      
      if (result.message) {
        message.value = result.message;
        setTimeout(() => {
          router.visit(result.redirect);
        }, 2000);
      } else {
        router.visit(result.redirect);
      }
    } else {
      alert(result.message || 'Erro ao criar a página.');
    }
  } catch (error) {
    alert('Erro ao criar a página. Tente novamente.');
  } finally {
    isCreating.value = false;
  }
};

const handleKeyPress = (event: KeyboardEvent) => {
  if (event.key === 'Enter') createShare();
};

const validateDuration = (e: KeyboardEvent) => {
  // Permite apenas números, backspace, delete, tab, escape, enter
  if (!((e.keyCode >= 48 && e.keyCode <= 57) || // números
        (e.keyCode >= 96 && e.keyCode <= 105) || // numpad
        [8, 9, 27, 13, 46].includes(e.keyCode))) { // backspace, tab, escape, enter, delete
    e.preventDefault();
  }
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
      
      <!-- Mensagem de sucesso/aviso -->
      <div v-if="message" class="mb-6 p-4 bg-yellow-100 border border-yellow-300 text-yellow-700 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-600 dark:text-yellow-400">
        {{ message }}
      </div>

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
            autocomplete="off"
            spellcheck="false"
          />
        </div>

        <div>
          <input
            id="password"
            v-model="password"
            type="password"
            maxlength="100"
            placeholder="Senha (opcional)"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-transparent dark:border-zinc-800 dark:text-white dark:placeholder-blue-300"
            @keypress="handleKeyPress"
            :disabled="isCreating"
            autocomplete="new-password"
            spellcheck="false"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Se definir uma senha, a página terá tempo de expiração
          </p>
        </div>

        <!-- Campos de duração (aparecem quando há senha) -->
        <div v-if="hasPassword" class="flex gap-2 items-end">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Tempo de duração
            </label>
            <input
              v-model.number="duration"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
              @keypress="validateDuration"
              :disabled="isCreating"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Unidade
            </label>
            <select
              v-model="unit"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
              :disabled="isCreating"
            >
              <option value="second">Segundos</option>
              <option value="minute">Minutos</option>
              <option value="hour">Horas</option>
            </select>
          </div>
        </div>

        <button
          @click="createShare"
          :disabled="!identifier.trim() || isCreating || (hasPassword && (!duration || duration < 1))"
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