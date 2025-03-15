<template>
    <div v-if="visible" class="confirmation-dialog">
      <div class="dialog-content">
        <h3>{{ title }}</h3>
        <p>{{ message }}</p>
        <div class="dialog-actions">
          <button class="btn btn-secondary" @click="cancel">Cancelar</button>
          <button class="btn btn-primary" @click="confirm">Confirmar</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  
  const props = defineProps({
    title: String,
    message: String,
    visible: Boolean,
  });
  
  const emit = defineEmits(['confirm', 'cancel']);
  
  const confirm = () => {
    emit('confirm');
  };
  
  const cancel = () => {
    emit('cancel');
  };
  </script>
  
  <style scoped>
  .confirmation-dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .dialog-content {
    background: white;
    padding: 20px;
    border-radius: 5px;
    text-align: center;
  }
  
  .dialog-actions {
    margin-top: 20px;
  }
  
  .btn {
    margin: 0 10px;
  }
  </style>