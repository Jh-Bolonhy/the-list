<template>
    <div
    :draggable="editingElement?.id !== element.id && !element.archived"
    :data-element-id="element.id"
    @dragstart="$emit('drag-start', $event, index)"
    @dragover.prevent="$emit('drag-over', $event, index)"
    @dragleave="$emit('drag-leave')"
    @drop="$emit('drop', $event, index)"
    @dragend="$emit('drag-end')"
    :style="combinedStyles"
    class="relative element-item-wrapper"
    :class="[
      elementClasses,
      'flex items-center p-4 rounded-lg transition-all duration-[400ms] border border-gray-300',
      draggingIndex === index
        ? 'shadow-2xl z-50 scale-[0.98] cursor-grabbing'
        : element.archived ? 'cursor-default' : 'cursor-grab',
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
    <!-- Checkbox (hidden while editing) -->
    <input
      v-if="editingElement?.id !== element.id"
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
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-0 focus:bg-gray-50 focus:border-gray-400 hover:bg-blue-50 hover:border-gray-300"
        />
        <textarea
          v-model="editingElement.description"
          rows="2"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-0 focus:bg-gray-50 focus:border-gray-400 hover:bg-blue-50 hover:border-gray-300 edit-description-textarea styled-scrollbar"
        ></textarea>
        <div class="flex space-x-2">
          <button
            @click="$emit('save-edit')"
            class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm transition-all duration-[400ms]"
          >
            {{ t('save') }}
          </button>
          <button
            @click="$emit('cancel-edit')"
            class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm transition-all duration-[400ms]"
          >
            {{ t('cancel') }}
          </button>
        </div>
      </div>
      <div v-else class="overflow-hidden relative">
        <div class="relative overflow-hidden" :style="{
          paddingRight: hasChildren && !element.archived ? '4.5rem' : '2.25rem'
        }">
          <div
            ref="titleScrollContainer"
            class="scrollable-text-container"
            @mousedown="startScroll($event, 'title')"
          >
            <h3
              ref="titleText"
              :class="[
                'text-lg font-medium whitespace-nowrap',
                element.completed ? 'line-through text-gray-500' : 'text-gray-800',
                isScrollingTitle ? 'cursor-grabbing' : 'cursor-grab'
              ]"
            >
              {{ element.title }}
            </h3>
          </div>
          <!-- Gradient fade overlay for title -->
          <div
            class="absolute right-0 pointer-events-none z-10"
            :style="{
              width: '2rem',
              height: '1.5rem',
              top: '50%',
              right: hasChildren && !element.archived ? '4.25rem' : '2rem',
              transform: 'translateY(-50%)',
              borderRadius: '0.25rem',
              background: `linear-gradient(to right, transparent, ${element.archived ? '#e5e7eb' : '#f9fafb'})`
            }"
          ></div>
          <!-- Copy to clipboard button (fixed width so fade ends right before it) -->
          <div class="title-action-slot">
            <!-- Lock button (parents only, not for archived) -->
            <button
              v-if="hasChildren && !element.archived"
              class="title-action-btn lock-button"
              :class="{ 'lock-active': lockedElementId === element.id }"
              @click.stop="$emit('toggle-lock', element.id)"
              title="Lock"
            >
              <!-- Closed lock when active -->
              <svg v-if="lockedElementId === element.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3V7a3 3 0 10-6 0v1c0 1.657 1.343 3 3 3z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11h10a2 2 0 012 2v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7a2 2 0 012-2z" />
              </svg>
              <!-- Open lock by default -->
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11V7a3 3 0 10-6 0" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11h10a2 2 0 012 2v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7a2 2 0 012-2z" />
              </svg>
            </button>
            <button
              class="title-action-btn copy-button"
              :class="{ 'copy-button-copied': isCopied }"
              @click.stop="copyElementContent"
              :title="t('copy') || 'Copy'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-2" />
              </svg>
            </button>
            <!-- Copy with children button (only for elements with children) -->
            <button
              v-if="hasChildren"
              class="title-action-btn copy-button"
              :class="{ 'copy-button-copied': isCopiedWithChildren }"
              @click.stop="copyElementWithChildren"
              :title="t('copyWithChildren') || 'Copy with children'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-2" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 4h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
              </svg>
            </button>
          </div>
        </div>
        <div v-if="element.description" class="relative mt-1 description-wrapper">
          <div
            ref="descriptionContainer"
            :class="[
              'description-container',
              'styled-scrollbar',
              isDescriptionExpanded ? 'description-expanded' : 'description-collapsed',
            ]"
            :style="descriptionHeight !== null ? { height: descriptionHeight + 'px' } : {}"
          >
            <p
              ref="descriptionText"
              :class="[
                'text-gray-600 description-text',
                element.completed ? 'line-through' : ''
              ]"
            >
              {{ element.description }}
            </p>
          </div>
          <!-- Animated expand/collapse button -->
          <button
            v-if="descriptionNeedsExpansion || isDescriptionExpanded"
            ref="descriptionToggleButton"
            @click.stop="toggleDescriptionExpansion"
            class="description-toggle-button"
            :class="{
              'description-toggle-collapsed': !isDescriptionExpanded,
              'description-toggle-expanded': isDescriptionExpanded
            }"
            :style="buttonTransformStyle"
            :title="isDescriptionExpanded ? 'Collapse description' : 'Expand description'"
          >
            <svg class="w-4 h-4 transition-all duration-[400ms]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>
        <p class="text-xs text-gray-400 mt-1 whitespace-nowrap overflow-hidden">
          {{ t('created') }}: {{ formatDate(element.created_at) }}
        </p>
      </div>
    </div>

    <!-- Collapse/Expand Button (only for elements with children) - centered across full element width -->
    <!-- Positioned absolutely relative to root element to center across entire gray box, on same line as date -->
    <button
      v-if="hasChildren"
      @click.stop="$emit('toggle-collapse', element.id)"
      class="absolute left-1/2 transform -translate-x-1/2 w-6 h-6 rounded-full bg-gray-200 hover:bg-gray-100 text-gray-600 hover:text-gray-500 flex items-center justify-center transition-all duration-[400ms] flex-shrink-0 z-10"
      :class="{ 'rotate-90': !isCollapsed }"
      title="Toggle children"
      style="bottom: 1rem;"
    >
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>

    <!-- Actions -->
    <div v-if="editingElement?.id !== element.id" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-28 flex flex-col space-y-2 z-30" style="pointer-events: auto;">
      <!-- Actions for active (non-archived) elements -->
      <template v-if="!element.archived">
        <button
          @click="$emit('start-edit', element)"
          class="w-full px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm transition-all duration-[400ms]"
        >
          {{ t('edit') }}
        </button>
        <button
          @click="$emit('archive', element.id)"
          class="w-full px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-sm font-medium transition-all duration-[400ms]"
        >
          {{ t('archive') }}
        </button>
      </template>
      <!-- Actions for archived elements -->
      <template v-else>
        <button
          @click="$emit('restore', element.id)"
          class="w-full px-3 py-1 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm transition-all duration-[400ms]"
        >
          {{ t('restore') }}
        </button>
        <button
          @click="$emit('remove', element.id)"
          class="w-full px-3 py-1 bg-pink-600 text-white rounded-md hover:bg-pink-700 text-sm transition-all duration-[400ms]"
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
    lockedElementId: {
      type: Number,
      default: null
    },
    isCollapsed: {
      type: Boolean,
      default: false
    },
    allElements: {
      type: Array,
      default: () => []
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
    'toggle-collapse',
    'toggle-lock'
  ],
  data() {
    return {
      scrollState: {
        isActive: false,
        type: null, // 'title' or 'description'
        startX: 0,
        startScrollLeft: 0
      },
      isDescriptionExpanded: false,
      descriptionNeedsExpansion: false,
      descriptionHeight: null, // Store computed height for animation
      buttonTransformY: 0, // Store button transform for animation
      isCopied: false, // Track if content was successfully copied
      isCopiedWithChildren: false, // Track if content with children was successfully copied
      copyFeedbackTimeoutId: null, // Timeout id for copy feedback reset
      copyWithChildrenFeedbackTimeoutId: null // Timeout id for copy with children feedback reset
    };
  },
  watch: {
    // When entering edit mode, make sure nothing blocks text selection (e.g. drag-scroll left userSelect='none')
    editingElement: {
      handler(newVal) {
        if (newVal?.id === this.element.id) {
          // Stop any active drag-scroll on title and restore selection
          if (this.scrollState?.isActive) {
            this.stopScroll();
          } else {
            document.body.style.userSelect = '';
          }
        }
      },
      deep: false
    }
  },
  computed: {
    buttonTransformStyle() {
      return {
        transform: `translateY(${this.buttonTransformY}px)`
      };
    },
    isScrollingTitle() {
      return this.scrollState.isActive && this.scrollState.type === 'title';
    },
    combinedStyles() {
      const indentAmount = this.element.level > 0 ? this.element.level * 1.25 : 0;
      const styles = {
        // 20px = 1.25rem (20 / 16 = 1.25)
        // Use margin-left for indentation, but reduce width to keep right edge aligned
        marginLeft: `${indentAmount}rem`,
        // Reduce width by indent amount to keep right edge aligned with container
        // This ensures elements don't overflow while maintaining proper indentation
        width: indentAmount > 0 ? `calc(100% - ${indentAmount}rem)` : '100%'
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
  },
  methods: {
    getScrollRefs(type) {
      const refsMap = {
        title: {
          container: this.$refs.titleScrollContainer,
          text: this.$refs.titleText
        }
      };
      return refsMap[type] || {};
    },
    checkDescriptionOverflow() {
      this.$nextTick(() => {
        const container = this.$refs.descriptionContainer;
        const text = this.$refs.descriptionText;

        if (!container || !text) {
          this.descriptionNeedsExpansion = false;
          return;
        }

        if (this.isDescriptionExpanded) {
          // When expanded, we don't need to show dots button
          this.descriptionNeedsExpansion = false;
          return;
        }

        // Check if text overflows when collapsed (more than 1 line)
        // Use getBoundingClientRect for accurate measurements
        const lineHeight = parseFloat(window.getComputedStyle(text).lineHeight) || 1.5 * 16;
        const maxHeight = lineHeight * 1; // 1 line when collapsed
        const textHeight = text.scrollHeight;
        const containerWidth = container.clientWidth;

        // Create a temporary clone to measure text width with proper wrapping
        const clone = text.cloneNode(true);
        clone.style.visibility = 'hidden';
        clone.style.position = 'absolute';
        clone.style.width = containerWidth + 'px';
        clone.style.whiteSpace = 'pre-wrap';
        clone.style.wordWrap = 'break-word';
        document.body.appendChild(clone);
        const textWidth = clone.scrollWidth;
        document.body.removeChild(clone);

        // Needs expansion if text is wider than container OR taller than 1 line
        this.descriptionNeedsExpansion = textWidth > containerWidth || textHeight > maxHeight;
      });
    },
    toggleDescriptionExpansion() {
      const container = this.$refs.descriptionContainer;
      const text = this.$refs.descriptionText;
      const button = this.$refs.descriptionToggleButton;

      if (!container || !text) return;

      // Remove animation-complete class if it exists
      container.classList.remove('animation-complete');

      const wasExpanded = this.isDescriptionExpanded;

      // Get current height - if 0, measure it first
      let currentHeight = container.offsetHeight;
      if (currentHeight === 0) {
        // Element might be collapsed, temporarily expand to measure
        container.style.height = 'auto';
        container.style.maxHeight = 'none';
        currentHeight = container.scrollHeight;
        container.style.height = '';
        container.style.maxHeight = '';
      }

      // Calculate target height
      let targetHeight;
      const lineHeight = parseFloat(window.getComputedStyle(text).lineHeight) || 24; // 1.5em = 24px typically

      if (wasExpanded) {
        // Collapsing: set to 1 line height
        targetHeight = lineHeight;
      } else {
        // Expanding: set to 4 lines or full content height
        // First, measure the full content height
        const tempHeight = container.style.height;
        const tempMaxHeight = container.style.maxHeight;
        container.style.height = 'auto';
        container.style.maxHeight = 'none';
        const contentHeight = text.scrollHeight;
        container.style.height = tempHeight;
        container.style.maxHeight = tempMaxHeight;

        const maxHeight = lineHeight * 4; // 4 lines
        targetHeight = Math.min(maxHeight, contentHeight);
      }

      // Calculate button position
      // Always use top positioning for consistency
      // When collapsed: button is at top of container (Y = 0)
      // When expanded: button is below container (Y = container height + margin)
      let targetButtonY;
      const margin = 4; // 0.25rem = 4px

      if (wasExpanded) {
        // Collapsing: move button to top of container
        targetButtonY = 0;
      } else {
        // Expanding: move button below container
        // Position = container height + small margin
        targetButtonY = targetHeight + margin;
      }

      // Get current button position
      let currentButtonY = this.buttonTransformY;

      // If position not initialized, calculate from current state
      if (currentButtonY === 0) {
        if (wasExpanded) {
          // Currently expanded, so button should be below container
          currentButtonY = currentHeight + margin;
        } else {
          // Currently collapsed, so button is at top
          currentButtonY = 0;
        }
      }

      // Set initial height and button position explicitly before state change
      container.style.height = currentHeight + 'px';
      container.style.maxHeight = 'none'; // Remove max-height to allow height animation
      if (button) {
        this.buttonTransformY = currentButtonY;
      }

      // Force reflow to ensure styles are applied
      void container.offsetHeight;
      if (button) {
        void button.offsetHeight;
      }

      // Change state
      this.isDescriptionExpanded = !this.isDescriptionExpanded;

      // Set target height and button position in next frame to trigger animation
      this.$nextTick(() => {
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            container.style.height = targetHeight + 'px';
            this.descriptionHeight = targetHeight;
            if (button) {
              this.buttonTransformY = targetButtonY;
            }

            // Wait for animation to complete
            setTimeout(() => {
              if (!container) return;

              if (this.isDescriptionExpanded) {
                // After expanding, mark as complete (scrolling is already enabled via CSS)
                container.classList.add('animation-complete');
              } else {
                // After collapsing, mark as complete
                container.classList.add('animation-complete');
                // Reset height to auto after animation
                setTimeout(() => {
                  if (container) {
                    container.style.height = '';
                    this.descriptionHeight = null;
                  }
                }, 50);
              }

              // Delay overflow check
              setTimeout(() => {
                this.checkDescriptionOverflow();
              }, 100);
            }, 400);
          });
        });
      });
    },
    startScroll(event, type) {
      const refs = this.getScrollRefs(type);
      const { container, text } = refs;

      if (!container || !text) return;

      // Check if text overflows
      if (text.scrollWidth <= container.clientWidth) {
        return; // Text fits, no need to scroll
      }

      // Prevent default to avoid text selection
      event.preventDefault();
      event.stopPropagation();

      // Set scroll state
      this.scrollState = {
        isActive: true,
        type,
        startX: event.clientX,
        startScrollLeft: container.scrollLeft
      };

      // Prevent text selection during scroll
      document.body.style.userSelect = 'none';

      // Add global event listeners for smooth scrolling
      document.addEventListener('mousemove', this.handleGlobalMouseMove);
      document.addEventListener('mouseup', this.handleGlobalMouseUp);
    },
    handleGlobalMouseMove(event) {
      if (this.scrollState.isActive) {
        this.onScrollMove(event);
      }
    },
    handleGlobalMouseUp() {
      this.stopScroll();
    },
    onScrollMove(event) {
      if (!this.scrollState.isActive) return;

      const refs = this.getScrollRefs(this.scrollState.type);
      const { container } = refs;
      if (!container) return;

      const deltaX = event.clientX - this.scrollState.startX;
      container.scrollLeft = this.scrollState.startScrollLeft - deltaX;
    },
    stopScroll() {
      // Reset scroll state
      this.scrollState = {
        isActive: false,
        type: null,
        startX: 0,
        startScrollLeft: 0
      };

      // Restore text selection
      document.body.style.userSelect = '';

      // Remove global event listeners
      document.removeEventListener('mousemove', this.handleGlobalMouseMove);
      document.removeEventListener('mouseup', this.handleGlobalMouseUp);
    },
    async copyElementContent() {
      try {
        const title = this.element.title || '';
        const description = this.element.description || '';
        const indentedDescription = description
          ? '    ' + description.replace(/\n/g, '\n    ')
          : '';
        const textToCopy = indentedDescription
          ? `${title}\n${indentedDescription}`
          : title;
        
        // Use Clipboard API to copy and verify success
        await navigator.clipboard.writeText(textToCopy);
        
        // Only show visual feedback if copy was successful
        if (this.copyFeedbackTimeoutId) {
          clearTimeout(this.copyFeedbackTimeoutId);
          this.copyFeedbackTimeoutId = null;
        }
        this.isCopied = true;
        
        // Remove visual feedback after animation duration (1.25s)
        this.copyFeedbackTimeoutId = setTimeout(() => {
          this.isCopied = false;
          this.copyFeedbackTimeoutId = null;
        }, 1250);
      } catch (err) {
        console.error('Failed to copy element content', err);
        // Don't set isCopied to true if copy failed
      }
    },
    /**
     * Get all descendants of an element recursively
     * @param {number} elementId - The element ID
     * @param {number} level - Current nesting level (for indentation)
     * @returns {Array} - Array of elements with their nesting level
     */
    getAllDescendants(elementId, level = 0) {
      const descendants = [];
      const children = this.allElements.filter(e => Number(e.parent_element_id) === Number(elementId));
      
      for (const child of children) {
        descendants.push({ element: child, level: level + 1 });
        // Recursively get descendants of this child
        const childDescendants = this.getAllDescendants(child.id, level + 1);
        descendants.push(...childDescendants);
      }
      
      return descendants;
    },
    /**
     * Format element content with indentation based on level
     * @param {Object} element - The element
     * @param {number} level - Nesting level
     * @returns {string} - Formatted text
     */
    formatElementContent(element, level) {
      const indent = '  '.repeat(level); // Structural indentation: 2 spaces per level
      const title = element.title || '';
      const description = element.description || '';
      const indentedDescription = description
        ? indent + '    ' + description.replace(/\n/g, '\n' + indent + '    ') // Description indentation: 4 spaces relative to title
        : '';
      return indentedDescription
        ? `${indent}${title}\n${indentedDescription}`
        : `${indent}${title}`;
    },
    async copyElementWithChildren() {
      try {
        // Get all descendants
        const descendants = this.getAllDescendants(this.element.id);
        
        // Format main element
        let textToCopy = this.formatElementContent(this.element, 0);
        
        // Format all descendants
        for (const { element, level } of descendants) {
          textToCopy += '\n\n' + this.formatElementContent(element, level);
        }
        
        // Use Clipboard API to copy and verify success
        await navigator.clipboard.writeText(textToCopy);
        
        // Only show visual feedback if copy was successful
        if (this.copyWithChildrenFeedbackTimeoutId) {
          clearTimeout(this.copyWithChildrenFeedbackTimeoutId);
          this.copyWithChildrenFeedbackTimeoutId = null;
        }
        this.isCopiedWithChildren = true;
        
        // Remove visual feedback after animation duration (1.25s)
        this.copyWithChildrenFeedbackTimeoutId = setTimeout(() => {
          this.isCopiedWithChildren = false;
          this.copyWithChildrenFeedbackTimeoutId = null;
        }, 1250);
      } catch (err) {
        console.error('Failed to copy element content with children', err);
        // Don't set isCopiedWithChildren to true if copy failed
      }
    }
  },
  mounted() {
    this.checkDescriptionOverflow();
    // Initialize button position if description is already expanded
    this.$nextTick(() => {
      if (this.isDescriptionExpanded) {
        const container = this.$refs.descriptionContainer;
        const button = this.$refs.descriptionToggleButton;
        if (container && button) {
          const containerHeight = container.offsetHeight;
          const margin = 4; // 0.25rem = 4px
          this.buttonTransformY = containerHeight + margin;
        }
      } else {
        // Collapsed state - button at top
        this.buttonTransformY = 0;
      }
    });
    // Check on window resize
    window.addEventListener('resize', this.checkDescriptionOverflow);
  },
  updated() {
    this.checkDescriptionOverflow();
  },
  beforeUnmount() {
    // Clean up on component destruction
    this.stopScroll();
    window.removeEventListener('resize', this.checkDescriptionOverflow);
  }
};
</script>

<style scoped>
/* Description wrapper */
.description-wrapper {
  position: relative;
  /* Ensure button is not clipped when it moves below container */
  overflow: visible;
  /* Add space below to accommodate button when expanded (button height ~32px + margin) */
  margin-bottom: 0.8rem;
}

/* Description container styles */
.description-container {
  position: relative;
  width: 100%;
  overflow: hidden;
  /* Lighter background than element - 2x lighter */
  /* Element: bg-gray-50 (#f9fafb) or bg-gray-200 (#e5e7eb) for archived */
  background-color: #fefefe; /* 2x lighter than previous #fcfcfd */
  border-radius: 0.25rem;
  /* Padding to prevent text clipping */
  /* This works for both collapsed and expanded states */
  padding-left: 0.5rem;
  padding-right: 2.5rem; /* Space for toggle button when collapsed */
  margin-top: 0.25rem;
}


.description-text {
  /* Preserve line breaks and whitespace, but allow wrapping */
  white-space: pre-wrap;
  word-wrap: break-word;
  word-break: break-word;
  margin: 0;
  padding: 0;
  /* Preserve structure with line breaks */
}

.description-collapsed {
  /* Collapsed: show only 1 line, but preserve structure in the text */
  overflow: hidden;
  line-height: 1.5;
  /* Height will be set dynamically via inline style */
  /* max-height is removed to allow height animation */
}

.description-collapsed .description-text {
  /* Apply line-clamp to text itself to preserve structure */
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  text-overflow: ellipsis;
  overflow: hidden;
  /* Ensure text doesn't get clipped */
  line-height: 1.5;
  margin: 0;
  padding: 0;
  /* Delay the application of display to allow max-height animation */
  /* Use opacity transition to smooth the text change */
  transition: opacity 0.1s ease-in-out;
}

.description-expanded {
  /* Expanded: show 4 lines initially, allow scroll if more than 4 lines */
  /* Height will be set dynamically via inline style */
  /* max-height is removed to allow height animation */
  line-height: 1.5;
  padding-right: 2.5rem; /* Space for scrollbar */
  /* Enable scrolling immediately - scrollbar should remain visible if content overflows */
  overflow-y: auto;
  overflow-x: hidden;
  /* Scrollbar styling is shared via .styled-scrollbar */
}

/* Edit modal textarea: max 4 lines visible, then scroll */
.edit-description-textarea {
  line-height: 1.5;
  /* 4 lines (1.5em each) + vertical padding (py-2 => 0.5rem top + 0.5rem bottom = 1rem) */
  max-height: calc(1.5em * 4 + 1rem);
  overflow-y: auto;
  overflow-x: hidden;
  resize: none;
}

.description-expanded.cursor-grabbing {
  cursor: grabbing; /* Only when actively scrolling */
}

/* Description text preserves structure in both states */
.description-text {
  /* Preserve line breaks and whitespace, but allow wrapping */
  white-space: pre-wrap;
  word-wrap: break-word;
  word-break: break-word;
  margin: 0;
  padding: 0;
}

/* Animated toggle button */
.description-toggle-button {
  position: absolute;
  right: 0;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem 0.5rem;
  /* Same colors as collapse button: bg-gray-200, text-gray-600 */
  background-color: rgb(229, 231, 235); /* bg-gray-200 */
  border: none;
  cursor: pointer;
  outline: none;
  color: rgb(75, 85, 99); /* text-gray-600 */
  border-radius: 0.25rem;
  /* Use will-change for smoother animation */
  will-change: transform;
}

.description-toggle-button:hover {
  /* Lightening effect instead of darkening */
  background-color: rgb(243, 244, 246); /* bg-gray-100 - lighter */
  color: rgb(107, 114, 128); /* text-gray-500 - lighter */
}

.description-toggle-button svg {
  transform-origin: center;
}

.description-toggle-collapsed {
  top: 0;
  /* Transform is handled via inline style for smooth animation */
}

.description-toggle-expanded {
  /* Always use top positioning, transform handles the vertical position */
  top: 0;
}

/* Rotate SVG icon when expanded */
.description-toggle-expanded svg {
  transform: rotate(180deg);
}

/* Copy button aligned with title row, similar size to toggle button */
.title-action-slot {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  z-index: 20;
  display: flex;
  gap: 0.25rem;
  height: 1.5rem;
}

.title-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 100%;
  padding: 0;
  background-color: rgb(229, 231, 235); /* bg-gray-200 */
  color: rgb(75, 85, 99); /* text-gray-600 */
  border: 1px solid transparent; /* keep layout stable; border color animates on success */
  border-radius: 0.25rem;
  cursor: pointer;
  outline: none;
}

.title-action-btn:hover {
  background-color: rgb(243, 244, 246); /* bg-gray-100 */
  color: rgb(107, 114, 128); /* text-gray-500 */
}

.lock-active {
  border-color: rgb(59, 130, 246); /* blue-500 */
}

.copy-button-copied {
  border-color: rgb(59, 130, 246); /* blue-500 - same color as edit button */
  animation: blink-border-only 1.25s ease-in-out forwards; /* keep final state (transparent) */
}

@keyframes blink-border-only {
  0% {
    border-color: rgb(59, 130, 246); /* blue-500 - 1. visible frame */
  }
  25% {
    border-color: transparent; /* 2. frame fades out */
  }
  50% {
    border-color: rgb(59, 130, 246); /* blue-500 - 3. frame becomes visible again */
  }
  75%, 100% {
    border-color: transparent; /* 4. frame fades out and stays transparent */
  }
}
</style>

