<template>
  <div class="mb-8 flex items-center relative" ref="headerRow">
    <!-- Left section: Headline + Settings Menu Icon -->
    <div class="flex items-center gap-4 flex-1 justify-start">
      <!-- Editable headline -->
      <h1 
        v-if="!isEditingHeadline && user"
        @click="$emit('start-editing', $event)"
        class="text-3xl font-bold text-gray-800 cursor-pointer hover:text-black transition-colors"
        :title="t('clickToEdit')"
      >
        {{ headlineDisplay }}
      </h1>
      <h1 
        v-else-if="!user"
        class="text-3xl font-bold text-gray-800"
      >
        {{ headlineDisplay }}
      </h1>
      <input
        v-else
        :value="headline"
        @input="$emit('update:headline', $event.target.value); $emit('headline-input')"
        @blur="$emit('finish-editing')"
        @keyup.enter="$emit('finish-editing')"
        @keyup.esc="$emit('cancel-editing')"
        type="text"
        ref="headlineInputRef"
        :style="inputStyle"
        class="text-3xl font-bold text-gray-800 bg-transparent border-b-4 border-gray-100 focus:outline-none focus:border-gray-200"
      />
      
      <!-- Settings Menu Icon -->
      <div class="relative">
        <button
          @click.stop="showSettingsMenu = !showSettingsMenu"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-all duration-300 ease-in-out"
          :title="t('settings')"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
        <!-- Dropdown Menu -->
        <transition name="fade">
          <div
            v-if="showSettingsMenu"
            class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200"
            @click.stop
            v-click-outside="() => showSettingsMenu = false"
          >
            <div class="py-1">
              <!-- Language Selector -->
              <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-2">
                <label class="text-sm font-medium text-gray-700">{{ t('language') }}</label>
                <select
                  :value="lang"
                  @change="$emit('lang-change', $event.target.value); showSettingsMenu = false"
                  class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                >
                  <option value="en">{{ t('english') }}</option>
                  <option value="ru">{{ t('russian') }}</option>
                </select>
              </div>
              <button
                @click="showSettingsMenu = false; $emit('logout')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-all duration-300 ease-in-out bg-gray-50"
              >
                {{ t('logout') }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
    
    <!-- Center: Round Add Button (absolutely centered) -->
    <button
      @click="$emit('show-add-modal')"
      class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-12 h-12 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-lg transition-all duration-300 ease-in-out hover:scale-110 flex items-center justify-center z-10"
      title="Add New Element"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
      </svg>
    </button>
    
    <!-- Right section: Show dropdown + Statistics -->
    <div class="flex items-center gap-4 flex-1 justify-end">
      <!-- Show dropdown -->
      <div class="flex items-center gap-2">
        <label for="viewMode" class="text-sm font-medium text-gray-700">{{ t('show') }}:</label>
        <select
          id="viewMode"
          :value="viewMode"
          @change="$emit('view-mode-change', $event.target.value)"
          class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white"
        >
          <option value="active">{{ t('active') }}</option>
          <option value="archived">{{ t('archived') }}</option>
          <option value="both">{{ t('both') }}</option>
        </select>
      </div>
      
      <!-- Statistics -->
      <div class="flex items-center">
        <!-- Active elements box -->
        <div class="px-3 py-1.5 bg-gray-50 rounded-lg text-sm text-gray-800 flex items-center gap-3 border border-gray-300">
          <span>{{ activeCount }}</span>
          <span class="text-gray-500">
            (<span class="line-through">{{ activeCompletedCount }}</span>)
          </span>
        </div>
        <!-- Separator -->
        <span class="text-gray-600 font-bold text-lg mx-2">/</span>
        <!-- Archived elements box -->
        <div class="px-3 py-1.5 bg-gray-200 rounded-lg text-sm text-gray-800 border border-gray-300">
          {{ archivedCount }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppHeader',
  props: {
    user: {
      type: Object,
      default: null
    },
    headline: {
      type: String,
      default: '',
      required: true
    },
    headlineDisplay: {
      type: String,
      required: true
    },
    isEditingHeadline: {
      type: Boolean,
      default: false
    },
    initialHeadlineWidth: {
      type: Number,
      default: null
    },
    maxHeadlineWidth: {
      type: Number,
      default: 0
    },
    viewMode: {
      type: String,
      required: true
    },
    activeCount: {
      type: Number,
      required: true
    },
    activeCompletedCount: {
      type: Number,
      required: true
    },
    archivedCount: {
      type: Number,
      required: true
    },
    lang: {
      type: String,
      required: true
    },
    t: {
      type: Function,
      required: true
    }
  },
  emits: [
    'start-editing',
    'update:headline',
    'headline-input',
    'finish-editing',
    'cancel-editing',
    'show-add-modal',
    'view-mode-change',
    'lang-change',
    'logout'
  ],
  data() {
    return {
      showSettingsMenu: false
    };
  },
  computed: {
    inputStyle() {
      if (this.initialHeadlineWidth) {
        return {
          width: this.initialHeadlineWidth + 'px',
          minWidth: this.initialHeadlineWidth + 'px',
          maxWidth: this.maxHeadlineWidth + 'px'
        };
      }
      return { maxWidth: this.maxHeadlineWidth + 'px' };
    }
  },
  directives: {
    'click-outside': {
      mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
          // Проверяем, что клик был вне элемента и его дочерних элементов
          if (!(el === event.target || el.contains(event.target))) {
            binding.value();
          }
        };
        document.addEventListener('click', el.clickOutsideEvent);
      },
      unmounted(el) {
        if (el.clickOutsideEvent) {
          document.removeEventListener('click', el.clickOutsideEvent);
        }
      }
    }
  }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

