<template>
  <transition name="modal-fade">
    <div
      v-if="show"
      class="fixed inset-0 bg-gray-900/70 flex items-center justify-center z-50 transition-opacity duration-300 ease-in-out"
      @click.self="$emit('close')"
    >
      <transition name="modal-scale">
        <div v-if="show" class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 transition-all duration-300 ease-in-out" @click.stop>
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">{{ t('addNewElement') }}</h2>
            <button
              @click="$emit('close')"
              class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-all duration-300 ease-in-out p-0 m-0"
              aria-label="Close"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <!-- Path display when locked element exists -->
          <div v-if="elementPath.length > 0" class="mb-4 p-3 bg-gray-50 rounded-md border border-gray-200">
            <div class="text-xs text-gray-500 mb-1">{{ t('createPath') || 'Creating under:' }}</div>
            <div class="text-sm text-gray-700 flex flex-wrap items-center gap-1">
              <span v-for="(item, index) in elementPath" :key="item.id" class="flex items-center">
                <span class="font-medium">{{ truncateTitle(item.title) }}</span>
                <span v-if="index < elementPath.length - 1" class="mx-1 text-gray-400">/</span>
              </span>
            </div>
          </div>
          <form @submit.prevent="$emit('submit', newElement)" class="space-y-4">
            <div>
              <label for="modal-title" class="block text-sm font-medium text-gray-700 mb-1">{{ t('title') }}</label>
              <input
                v-model="newElement.title"
                type="text"
                id="modal-title"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('title')"
                required
                ref="titleInput"
              />
            </div>
            <div>
              <label for="modal-description" class="block text-sm font-medium text-gray-700 mb-1">{{ t('description') }}</label>
              <textarea
                v-model="newElement.description"
                id="modal-description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('description') + ' (' + t('optional') + ')'"
              ></textarea>
            </div>
            <div class="flex space-x-3">
              <button
                type="submit"
                class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 ease-in-out"
              >
                {{ t('add') }}
              </button>
              <button
                type="button"
                @click="$emit('close')"
                class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-300 ease-in-out"
              >
                {{ t('cancel') }}
              </button>
            </div>
          </form>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script>
import { ref, computed, watch, nextTick } from 'vue';

export default {
  name: 'AddElementModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    t: {
      type: Function,
      required: true
    },
    lockedElementId: {
      type: Number,
      default: null
    },
    elements: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'submit'],
  setup(props, { emit }) {
    const newElement = ref({ title: '', description: '' });
    const titleInput = ref(null);

    // Build path from root to locked element
    const elementPath = computed(() => {
      if (!props.lockedElementId || !props.elements.length) {
        return [];
      }

      const path = [];
      const elementsById = new Map(props.elements.map(e => [e.id, e]));
      let currentId = props.lockedElementId;

      // Traverse from locked element to root
      while (currentId !== null && currentId !== undefined) {
        const element = elementsById.get(currentId);
        if (!element) break;
        
        path.unshift(element); // Add to beginning to build path from root
        currentId = element.parent_element_id;
      }

      return path;
    });

    const truncateTitle = (title) => {
      if (!title) return '';
      return title.length > 20 ? title.substring(0, 20) + '...' : title;
    };

    const handleSubmit = () => {
      emit('submit', { ...newElement.value });
      newElement.value = { title: '', description: '' };
    };

    watch(() => props.show, (newVal) => {
      if (newVal) {
        newElement.value = { title: '', description: '' };
        nextTick(() => {
          if (titleInput.value) {
            titleInput.value.focus();
          }
        });
      }
    });

    return {
      newElement,
      titleInput,
      handleSubmit,
      elementPath,
      truncateTitle
    };
  }
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

