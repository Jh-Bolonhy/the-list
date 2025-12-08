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
    class="relative"
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
    <div class="flex-1 pr-32 overflow-hidden min-w-0 relative">
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
      <div v-else class="overflow-hidden relative">
        <div class="relative">
          <h3 
            :class="[
              'text-lg font-medium whitespace-nowrap overflow-hidden',
              element.completed ? 'line-through text-gray-500' : 'text-gray-800'
            ]"
          >
            {{ element.title }}
          </h3>
          <!-- Gradient fade overlay for title -->
          <div 
            class="absolute right-0 top-0 bottom-0 pointer-events-none z-10"
            :style="{
              width: '2.25rem',
              background: `linear-gradient(to right, transparent, ${element.archived ? '#e5e7eb' : '#f9fafb'})`
            }"
          ></div>
        </div>
        <div v-if="element.description" class="relative mt-1">
          <p 
            :class="[
              'text-gray-600 whitespace-nowrap overflow-hidden',
              element.completed ? 'line-through' : ''
            ]"
          >
            {{ element.description }}
          </p>
          <!-- Gradient fade overlay for description -->
          <div 
            class="absolute right-0 top-0 bottom-0 pointer-events-none z-10"
            :style="{
              width: '2.25rem',
              background: `linear-gradient(to right, transparent, ${element.archived ? '#e5e7eb' : '#f9fafb'})`
            }"
          ></div>
        </div>
        <p class="text-xs text-gray-400 mt-2 whitespace-nowrap overflow-hidden">
          {{ t('created') }}: {{ formatDate(element.created_at) }}
        </p>
      </div>
    </div>
    
    <!-- Collapse/Expand Button (only for elements with children) - centered across full element width -->
    <!-- Positioned absolutely relative to root element to center across entire gray box, on same line as date -->
    <button
      v-if="hasChildren"
      @click.stop="$emit('toggle-collapse', element.id)"
      class="absolute left-1/2 transform -translate-x-1/2 w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-300 text-gray-600 hover:text-gray-800 flex items-center justify-center transition-all duration-200 flex-shrink-0 z-10"
      :class="{ 'rotate-90': !isCollapsed }"
      title="Toggle children"
      style="bottom: 1rem;"
    >
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>
    
    <!-- Actions -->
    <div v-if="editingElement?.id !== element.id" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-28 flex flex-col space-y-2 z-20">
      <!-- Actions for active (non-archived) elements -->
      <template v-if="!element.archived">
        <button
          @click="$emit('start-edit', element)"
          class="w-full px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm transition-all duration-300 ease-in-out"
        >
          {{ t('edit') }}
        </button>
        <button
          @click="$emit('archive', element.id)"
          class="w-full px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-sm font-medium transition-all duration-300 ease-in-out"
        >
          {{ t('archive') }}
        </button>
      </template>
      <!-- Actions for archived elements -->
      <template v-else>
        <button
          @click="$emit('restore', element.id)"
          class="w-full px-3 py-1 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm transition-all duration-300 ease-in-out"
        >
          {{ t('restore') }}
        </button>
        <button
          @click="$emit('remove', element.id)"
          class="w-full px-3 py-1 bg-pink-600 text-white rounded-md hover:bg-pink-700 text-sm transition-all duration-300 ease-in-out"
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
    },
    hasChildren: {
      type: Boolean,
      default: false
    },
    isCollapsed: {
      type: Boolean,
      default: false
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
    'remove',
    'toggle-collapse'
  ],
  computed: {
    combinedStyles() {
      const styles = {
        // 20px = 1.25rem (20 / 16 = 1.25)
        marginLeft: this.element.level > 0 ? `${this.element.level * 1.25}rem` : '0'
      };
      
      // Базовый отрицательный marginTop для визуального сжатия вложенных элементов
      const baseMarginTop = (1 - Math.pow(0.8, this.element.level + 1)) * 0.75;
      
      // Проверяем, есть ли классы для drag-and-drop маржи
      // 20px = 1.25rem (20 / 16 = 1.25), 40.8px = 2.55rem (40.8 / 16 = 2.55)
      // Если есть mt-[2.55rem], используем его (специальный случай для первого корневого элемента)
      if (this.elementClasses && this.elementClasses.includes('mt-[2.55rem]')) {
        styles.marginTop = '2.55rem';
      } else if (this.elementClasses && this.elementClasses.includes('mt-[1.25rem]')) {
        styles.marginTop = '1.25rem';
      } else {
        // Иначе используем базовый отрицательный отступ
        styles.marginTop = `-${baseMarginTop}rem`;
      }
      
      // Если есть mb-[1.25rem], добавляем нижнюю маржу
      if (this.elementClasses && this.elementClasses.includes('mb-[1.25rem]')) {
        styles.marginBottom = '1.25rem';
      }
      
      return styles;
    }
  }
};
</script>

