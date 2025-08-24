<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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
}

const props = defineProps<Props>();

// Estado do componente
const file = ref<File | null>(null);
const duration = ref<number>(1);
const unit = ref<'second' | 'minute' | 'hour'>('hour');
const isUploading = ref(false);
const uploadProgress = ref(0);
const isDragOver = ref(false);
const errors = ref<Record<string, string[]>>({});
const message = ref('');

// Computed properties
const canAddMoreFiles = computed(() => props.existingFiles.length < props.maxFiles);
const fileInputRef = ref<HTMLInputElement>();

// Formatação do tamanho máximo
const maxFileSizeFormatted = computed(() => {
  const gb = props.maxFileSize / (1024 * 1024 * 1024);
  return `${gb}GB`;
});

// Funções de drag and drop
const handleDragOver = (e: DragEvent) => {
  e.preventDefault();
  isDragOver.value = true;
};

const handleDragLeave = (e: DragEvent) => {
  e.preventDefault();
  isDragOver.value = false;
};

const handleDrop = (e: DragEvent) => {
  e.preventDefault();
  isDragOver.value = false;
  const files = e.dataTransfer?.files;
  if (files && files.length > 0) handleFileSelect(files[0]);
};

// Função para selecionar arquivo
const handleFileSelect = (selectedFile: File) => {
  if (selectedFile.size > props.maxFileSize) {
    alert(`Arquivo muito grande. Tamanho máximo: ${maxFileSizeFormatted.value}`);
    return;
  }
  
  file.value = selectedFile;
  errors.value = {};
};

// Função para selecionar arquivo via input
const onFileInputChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) handleFileSelect(target.files[0]);
};

// Função para remover arquivo selecionado
const removeSelectedFile = () => {
  file.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
};

// Função para validar entrada numérica
const validateDuration = (e: KeyboardEvent) => {
  // Permite apenas números, backspace, delete, tab, escape, enter
  if (!((e.keyCode >= 48 && e.keyCode <= 57) || // números
        (e.keyCode >= 96 && e.keyCode <= 105) || // numpad
        [8, 9, 27, 13, 46].includes(e.keyCode))) { // backspace, tab, escape, enter, delete
    e.preventDefault();
  }
};

// Função para fazer upload
const uploadFile = async () => {
  if (!file.value) {
    alert('Por favor, selecione um arquivo.');
    return;
  }

  if (!duration.value || duration.value < 1) {
    alert('Por favor, defina um tempo de duração válido.');
    return;
  }

  const formData = new FormData();
  formData.append('file', file.value);
  formData.append('duration', duration.value.toString());
  formData.append('unit', unit.value);

  isUploading.value = true;
  uploadProgress.value = 0;
  errors.value = {};
  message.value = '';

  try {
    // Simula progresso de upload
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) uploadProgress.value += Math.random() * 10;
    }, 100);

    const response = await fetch(`/${props.identifier}/upload`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });

    clearInterval(progressInterval);
    uploadProgress.value = 100;

    const result = await response.json();

    if (result.success) {
      // Sucesso - recarrega a página para mostrar o arquivo
      if (result.message) {
        message.value = result.message;
        setTimeout(() => { router.reload() }, 2000);
      } else router.reload();
    } else {
      if (result.errors) errors.value = result.errors;
      else alert(result.message || 'Erro ao fazer upload do arquivo.');
    }
  } catch (error) {
    alert('Erro ao fazer upload do arquivo. Tente novamente.');
  } finally {
    isUploading.value = false;
    uploadProgress.value = 0;
  }
};

// Função para excluir arquivo existente
const deleteFile = async (fileId: number) => {
  if (!confirm('Tem certeza que deseja excluir este arquivo?')) return;
  
  try {
    const response = await fetch(`/file/${fileId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Content-Type': 'application/json',
      },
    });
    
    const result = await response.json();
    
    if (result.success) router.reload();
    else alert(result.message || 'Erro ao excluir arquivo.');
  } catch (error) {
    alert('Erro ao excluir arquivo. Tente novamente.');
  }
};

// Função para voltar à página inicial
const goHome = () => router.visit('/')
</script>

<template>
<Head :title="`Compartilhamento: ${identifier}`" />

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-zinc-900 dark:to-black">
  <!-- Header -->
  <div class="backdrop-blur-sm">
    <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <button
          @click="goHome"
          class="text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center space-x-2 hover:underline cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Voltar</span>
        </button>
        <div class="text-sm text-gray-500 dark:text-gray-400">|</div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ identifier }}</h1>
      </div>
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ existingFiles.length }}/{{ maxFiles }} arquivos
      </div>
    </div>
  </div>

  <div class="max-w-4xl mx-auto px-6 py-8 space-y-8">
    <!-- Arquivos existentes -->
    <div v-if="existingFiles.length > 0" class="space-y-4">
      <h2 class="text-lg font-medium text-gray-900 dark:text-white">Arquivos compartilhados</h2>

      <div class="space-y-3">
        <div
          v-for="existingFile in existingFiles"
          :key="existingFile.id"
          class="bg-white dark:bg-zinc-900 rounded-lg p-4 flex items-center justify-between shadow-sm border border-gray-200 dark:border-gray-700"
        >
          <div class="flex-1">
            <div class="font-medium text-gray-900 dark:text-white">
              {{ existingFile.original_name }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ existingFile.size }} • Expira em {{ existingFile.expires_at }}
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <a
              :href="`/download/${existingFile.id}`"
              class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:!bg-blue-500 hover:!text-white dark:bg-blue-900 dark:text-blue-300"
            >
              Download
            </a>
            <button
              @click="deleteFile(existingFile.id)"
              class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:!bg-red-200 dark:bg-red-900 dark:text-red-300 hover:!text-red-800 cursor-pointer"
            >
              Excluir
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Formulário de upload -->
    <div v-if="canAddMoreFiles" class="bg-white dark:bg-zinc-900/60 rounded-lg shadow-xl p-8">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
        {{ existingFiles.length === 0 ? 'Adicionar primeiro arquivo' : 'Adicionar outro arquivo' }}
      </h2>

      <!-- Mensagem de sucesso/aviso -->
      <div v-if="message" class="mb-6 p-4 bg-yellow-100 border border-yellow-300 text-yellow-700 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-600 dark:text-yellow-400">
        {{ message }}
      </div>

      <!-- Área de upload -->
      <div
        @dragover="handleDragOver"
        @dragleave="handleDragLeave"
        @drop="handleDrop"
        @click="fileInputRef?.click()"
        class="relative border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors"
        :class="{
          'border-blue-400 bg-blue-50 dark:bg-blue-900/20': isDragOver,
          'border-gray-300 dark:border-gray-600 hover:border-gray-400': !isDragOver && !file,
          'border-green-400 bg-green-50 dark:bg-green-900/20': file
        }"
      >
        <input
          ref="fileInputRef"
          type="file"
          class="hidden"
          @change="onFileInputChange"
          :disabled="isUploading"
        />
        
        <div v-if="!file" class="space-y-4">
          <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <div>
            <p class="text-lg font-medium text-gray-900 dark:text-white">Arraste um arquivo aqui</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ou clique para selecionar</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Máximo {{ maxFileSizeFormatted }}</p>
          </div>
        </div>
        
        <div v-else class="space-y-4">
          <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ file.name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ (file.size / (1024 * 1024)).toFixed(2) }} MB</p>
            <button @click.stop="removeSelectedFile" class="mt-2 text-sm text-red-600 hover:text-red-700 dark:text-red-400 hover:underline cursor-pointer">
              Remover arquivo
            </button>
          </div>
        </div>
      </div>

      <!-- Controles de duração -->
      <div class="mt-6 flex flex-wrap gap-2 items-end space-x-4">
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tempo de duração</label>
          <input
            v-model.number="duration"
            type="number"
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
            @keypress="validateDuration"
            :disabled="isUploading"
          />
          <div v-if="errors.duration" class="text-red-600 text-sm mt-1">{{ errors.duration[0] }}</div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unidade</label>
          <select
            v-model="unit"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
            :disabled="isUploading"
          >
            <option value="second">Segundos</option>
            <option value="minute">Minutos</option>
            <option value="hour">Horas</option>
          </select>
          <div v-if="errors.unit" class="text-red-600 text-sm mt-1">{{ errors.unit[0] }}</div>
        </div>
        
        <button
          @click="uploadFile"
          :disabled="!file || isUploading || !duration"
          class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-zinc-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors cursor-pointer"
        >
          {{ isUploading ? 'Enviando...' : 'Enviar' }}
        </button>
      </div>
      
      <!-- Barra de progresso -->
      <div v-if="isUploading" class="mt-6">
        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
          <span>Enviando arquivo...</span>
          <span>{{ Math.round(uploadProgress) }}%</span>
        </div>
        <div class="w-full bg-zinc-200 rounded-full h-2 dark:bg-zinc-700">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="`width: ${uploadProgress}%`"
          ></div>
        </div>
      </div>
      
      <!-- Erros de validação -->
      <div v-if="errors.file" class="mt-4 text-red-600 text-sm">{{ errors.file[0] }}</div>
    </div>
    
    <!-- Limite atingido -->
    <div v-else class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-8 text-center">
      <div class="text-gray-500 dark:text-gray-400">
        <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          Limite de arquivos atingido
        </h3>
        <p>
          Você já tem {{ maxFiles }} arquivos nesta página. 
          Exclua um arquivo existente para adicionar um novo.
        </p>
      </div>
    </div>

    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
      <h3 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
        Como funciona:
      </h3>
      <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
        <li>• Crie uma página única com um nome personalizado</li>
        <li>• Compartilhe até 2 arquivos por página (máx. 50GB cada)</li>
        <li>• Defina o tempo de expiração (máx. 24 horas)</li>
        <li>• Compartilhe o link com quem quiser</li>
      </ul>
    </div>
  </div>
</div>
</template>
