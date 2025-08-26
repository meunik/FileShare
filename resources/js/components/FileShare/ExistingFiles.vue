<script setup lang="ts">
import { router } from '@inertiajs/vue3';

interface ExistingFile {
  id: number;
  original_name: string;
  size: string;
  expires_at: string;
}

interface Props {
  files: ExistingFile[];
}

const props = defineProps<Props>();

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
    
    if (result.success) {
      router.reload();
    } else {
      alert(result.message || 'Erro ao excluir arquivo.');
    }
  } catch (error) {
    alert('Erro ao excluir arquivo. Tente novamente.');
  }
};
</script>

<template>
  <div v-if="files.length > 0" class="space-y-4">
    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Arquivos compartilhados</h2>

    <div class="space-y-3">
      <div
        v-for="file in files"
        :key="file.id"
        class="bg-white dark:bg-zinc-900 rounded-lg p-4 flex items-center justify-between shadow-sm border border-gray-200 dark:border-gray-700"
      >
        <div class="flex-1">
          <div class="font-medium text-gray-900 dark:text-white break-all">
            {{ file.original_name }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ file.size }} • Expira em {{ file.expires_at }}
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <a
            :href="`/download/${file.id}`"
            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:!bg-blue-500 hover:!text-white dark:bg-blue-900 dark:text-blue-300 no-underline"
          >
            Download
          </a>
          <button
            @click="deleteFile(file.id)"
            class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:!bg-red-200 dark:bg-red-900 dark:text-red-300 hover:!text-red-800 cursor-pointer"
          >
            Excluir
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
