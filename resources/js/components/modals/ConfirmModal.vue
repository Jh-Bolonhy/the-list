<template>
  <transition name="modal-fade">
    <div
      v-if="show"
      class="fixed inset-0 bg-gray-900/70 flex items-center justify-center z-50 transition-opacity duration-300 ease-in-out"
      @click.self="$emit('close')"
    >
      <transition name="modal-scale">
        <div
          class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 transform transition-all duration-300 ease-in-out"
        >
          <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('confirm') }}</h3>
          <p class="text-gray-600 mb-6">{{ message }}</p>
          <div class="flex space-x-3">
            <button
              @click="$emit('confirm')"
              class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 ease-in-out"
            >
              {{ t('confirm') }}
            </button>
            <button
              @click="$emit('close')"
              class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-300 ease-in-out"
            >
              {{ t('cancel') }}
            </button>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'ConfirmModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    message: {
      type: String,
      required: true
    },
    t: {
      type: Function,
      required: true
    }
  },
  emits: ['close', 'confirm']
};
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease-in-out;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
  transition: all 0.3s ease-in-out;
}

.modal-scale-enter-from,
.modal-scale-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>

