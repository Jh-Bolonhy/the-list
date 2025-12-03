<template>
  <div
    :draggable="true"
    :data-element-id="element.id"
    @dragstart="$emit('drag-start', $event, index)"
    @dragover.prevent="$emit('drag-over', $event, index)"
    @dragleave="$emit('drag-leave')"
    @drop="$emit('drop', $event, index)"
    @dragend="$emit('drag-end')"
    :style="combinedStyles"
    :class="[
      elementClasses,
      'flex items-center p-4 rounded-lg transition-all duration-300 ease-in-out border border-gray-300',
      draggingIndex === index 
        ? 'shadow-2xl z-50 scale-[0.98] cursor-grabbing' 
        : 'cursor-grab',
      element.archived 
        ? 'bg-gray-200 hover:bg-gray-300' 
        : 'bg-gray-50 hover:bg-gray-100',
      draggingIndex !== null && draggingIndex !== index
        ? 'opacity-90' 
        : '',
      dragOverIndex === index && draggingIndex !== null && draggingIndex !== index ? 'mt-1' : '',
      dragOverIndex === index + 1 && draggingIndex !== null && draggingIndex !== index ? 'mb-1' : '',
      hoverElementIndex === index && hoverElementPart === 'middle' && draggingIndex !== null && draggingIndex !== index
        ? 'shadow-lg scale-[1.02]' 
        : ''
    ]"
  >
    <!-- Checkbox -->
    <input
      type="checkbox"
      :checked="element.completed"
      @change="$emit('toggle', element)"
      class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-4"
    />
    
    <!-- Element Content -->
    <div class="flex-1">
      <div v-if="editingElement?.id === element.id" class="space-y-2">
        <input
          v-model="editingElement.title"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <textarea
          v-model="editingElement.description"
          rows="2"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        ></textarea>
        <div class="flex space-x-2">
          <button
            @click="$emit('save-edit')"
            class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm transition-all duration-300 ease-in-out"
          >
            {{ t('save') }}
          </button>
          <button
            @click="$emit('cancel-edit')"
            class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm transition-all duration-300 ease-in-out"
          >
            {{ t('cancel') }}
          </button>
        </div>
      </div>
      <div v-else>
        <h3 
          :class="[
            'text-lg font-medium',
            element.completed ? 'line-through text-gray-500' : 'text-gray-800'
          ]"
        >
          {{ element.title }}
        </h3>
        <p 
          v-if="element.description"
          :class="[
            'text-gray-600 mt-1',
            element.completed ? 'line-through' : ''
          ]"
        >
          {{ element.description }}
        </p>
        <p class="text-xs text-gray-400 mt-2">
          {{ t('created') }}: {{ formatDate(element.created_at) }}
        </p>
      </div>
    </div>
    
    <!-- Actions -->
    <div v-if="editingElement?.id !== element.id" class="flex space-x-2 ml-4">
      <!-- Actions for active (non-archived) elements -->
      <template v-if="!element.archived">
        <button
          @click="$emit('start-edit', element)"
          class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm transition-all duration-300 ease-in-out"
        >
          {{ t('edit') }}
        </button>
        <button
          @click="$emit('archive', element.id)"
          class="px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-sm font-medium transition-all duration-300 ease-in-out"
        >
          {{ t('archive') }}
        </button>
      </template>
      <!-- Actions for archived elements -->
      <template v-else>
        <button
          @click="$emit('restore', element.id)"
          class="px-3 py-1 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm transition-all duration-300 ease-in-out"
        >
          {{ t('restore') }}
        </button>
        <button
          @click="$emit('remove', element.id)"
          class="px-3 py-1 bg-pink-600 text-white rounded-md hover:bg-pink-700 text-sm transition-all duration-300 ease-in-out"
        >
          {{ t('remove') }}
        </button>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ElementItem',
  props: {
    element: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      required: true
    },
    editingElement: {
      type: Object,
      default: null
    },
    draggingIndex: {
      type: Number,
      default: null
    },
    dragOverIndex: {
      type: Number,
      default: null
    },
    hoverElementIndex: {
      type: Number,
      default: null
    },
    hoverElementPart: {
      type: String,
      default: null
    },
    elementClasses: {
      type: String,
      default: ''
    },
    t: {
      type: Function,
      required: true
    },
    formatDate: {
      type: Function,
      required: true
    }
  },
  emits: [
    'drag-start',
    'drag-over',
    'drag-leave',
    'drop',
    'drag-end',
    'toggle',
    'start-edit',
    'save-edit',
    'cancel-edit',
    'archive',
    'restore',
    'remove'
  ],
  computed: {
    combinedStyles() {
      const styles = {
        marginLeft: this.element.level > 0 ? `${this.element.level * 20}px` : '0'
      };
      
      // Базовый отрицательный marginTop для визуального сжатия вложенных элементов
      const baseMarginTop = (1 - Math.pow(0.8, this.element.level + 1)) * 0.75;
      
      // Проверяем, есть ли классы для drag-and-drop маржи
      // Если есть mt-[30px], используем его (перезаписываем базовый)
      if (this.elementClasses && this.elementClasses.includes('mt-[30px]')) {
        styles.marginTop = '30px';
      } else {
        // Иначе используем базовый отрицательный отступ
        styles.marginTop = `-${baseMarginTop}rem`;
      }
      
      // Если есть mb-[30px], добавляем нижнюю маржу
      if (this.elementClasses && this.elementClasses.includes('mb-[30px]')) {
        styles.marginBottom = '30px';
      }
      
      return styles;
    }
  }
};
</script>

