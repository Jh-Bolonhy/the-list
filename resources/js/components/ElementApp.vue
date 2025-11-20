<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ t('elementList') }}</h1>
        <div class="mb-4 flex items-center justify-between">
          <LanguageSwitcher :lang="lang" :t="t" @update:lang="setLang" />
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

        <!-- Element List -->
        <div class="space-y-4">
          <div class="flex items-center mb-4 relative">
            <h2 class="text-xl font-semibold">{{ t('yourElements') }}</h2>
            <!-- Round Add Button (only show for active or both view) - centered -->
            <button
              v-if="viewMode === 'active' || viewMode === 'both'"
              @click="showAddModal = true"
              class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-12 h-12 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-lg transition-all hover:scale-110 flex items-center justify-center z-10"
              title="Add New Element"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
              </svg>
            </button>
            <div class="ml-auto flex items-center gap-2">
              <label for="viewMode" class="text-sm font-medium text-gray-700">{{ t('show') }}:</label>
              <select
                id="viewMode"
                v-model="viewMode"
                @change="loadElements"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white"
              >
                <option value="active">{{ t('active') }}</option>
                <option value="archived">{{ t('archived') }}</option>
                <option value="both">{{ t('both') }}</option>
              </select>
            </div>
          </div>
          
          <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-600">{{ t('loading') }}</p>
          </div>

          <div v-else-if="elements.length === 0" class="text-center py-8">
            <p class="text-gray-500">{{ t('noElements') }}</p>
          </div>

          <div v-else class="space-y-3">
            <!-- Drop zone at the top -->
            <div
              v-if="draggingIndex !== null"
              @dragover.prevent="handleDragOver($event, -1)"
              @drop="handleDrop($event, -1)"
              @dragenter.prevent="dragOverIndex = -1"
              class="h-8 mb-2 cursor-move transition-colors"
              :class="dragOverIndex === -1 ? 'bg-blue-100' : 'bg-transparent'"
            >
              <div
                v-if="dragOverIndex === -1"
                class="h-1 bg-blue-500 rounded-full"
              ></div>
            </div>
            
            <template v-for="(element, index) in elements" :key="element.id">
              <!-- Drop zone indicator above element -->
              <div
                v-if="dragOverIndex === index && draggingIndex !== null && draggingIndex < index"
                class="h-1 bg-blue-500 rounded-full mb-2 -mt-2"
              ></div>
              
              <div
                :draggable="true"
                @dragstart="handleDragStart($event, index)"
                @dragover.prevent="handleDragOver($event, index)"
                @dragleave="handleDragLeave"
                @drop="handleDrop($event, index)"
                @dragend="handleDragEnd"
                :class="[
                  'flex items-center p-4 rounded-lg transition-colors border border-gray-300 cursor-move',
                  element.archived 
                    ? 'bg-gray-200 hover:bg-gray-300' 
                    : 'bg-gray-50 hover:bg-gray-100',
                  draggingIndex === index ? 'opacity-50' : '',
                  dragOverIndex === index && draggingIndex !== null && draggingIndex > index ? 'border-blue-500 border-2' : ''
                ]"
              >
              <!-- Checkbox -->
              <input
                type="checkbox"
                :checked="element.completed"
                @change="toggleElement(element)"
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
                      @click="saveEdit"
                      class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm"
                    >
                      {{ t('save') }}
                    </button>
                    <button
                      @click="cancelEdit"
                      class="px-3 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm"
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
                    @click="startEdit(element)"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm"
                  >
                    {{ t('edit') }}
                  </button>
                  <button
                    @click="archiveElement(element.id)"
                    class="px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-sm font-medium"
                  >
                    {{ t('archive') }}
                  </button>
                </template>
                <!-- Actions for archived elements -->
                <template v-else>
                  <button
                    @click="restoreElement(element.id)"
                    class="px-3 py-1 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm"
                  >
                    {{ t('restore') }}
                  </button>
                  <button
                    @click="removeElement(element.id)"
                    class="px-3 py-1 bg-pink-600 text-white rounded-md hover:bg-pink-700 text-sm"
                  >
                    {{ t('remove') }}
                  </button>
                </template>
              </div>
            </div>
            
            <!-- Drop zone indicator below element when dragging down -->
            <div
              v-if="dragOverIndex === index && draggingIndex !== null && draggingIndex > index"
              class="h-1 bg-blue-500 rounded-full mt-2"
            ></div>
            </template>
            
            <!-- Drop zone at the bottom -->
            <div
              v-if="draggingIndex !== null"
              @dragover.prevent="handleDragOver($event, elements.length)"
              @drop="handleDrop($event, elements.length)"
              @dragenter.prevent="dragOverIndex = elements.length"
              class="h-8 mt-2 cursor-move transition-colors"
              :class="dragOverIndex === elements.length ? 'bg-blue-100' : 'bg-transparent'"
            >
              <div
                v-if="dragOverIndex === elements.length"
                class="h-1 bg-blue-500 rounded-full"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Element Modal -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-gray-900/70 flex items-center justify-center z-50"
      @click.self="closeAddModal"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4" @click.stop>
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">{{ t('addNewElement') }}</h2>
          <button
            @click="closeAddModal"
            class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors p-0 m-0"
            aria-label="Close"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <form @submit.prevent="handleAddElement" class="space-y-4">
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
              class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
              {{ t('add') }}
            </button>
            <button
              type="button"
              @click="closeAddModal"
              class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors"
            >
              {{ t('cancel') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import en from '../lang/en.js';
import ru from '../lang/ru.js';
import LanguageSwitcher from './LanguageSwitcher.vue';

const locales = { en, ru };

export default {
  name: 'ElementApp',
  components: { LanguageSwitcher },
  data() {
    return {
      elements: [],
      loading: true,
      viewMode: 'active',
      showAddModal: false,
      newElement: {
        title: '',
        description: ''
      },
      editingElement: null,
      lang: 'en',
      allElements: [], // Store all elements for statistics
      draggingIndex: null,
      dragOverIndex: null,
    };
  },
  computed: {
    activeCount() {
      return this.allElements.filter(e => !e.archived).length;
    },
    activeCompletedCount() {
      const active = this.allElements.filter(e => !e.archived);
      return active.filter(e => e.completed).length;
    },
    archivedCount() {
      return this.allElements.filter(e => e.archived).length;
    }
  },
  methods: {
    t(key) {
      return locales[this.lang][key] || key;
    },
    setLang(newLang) {
      this.lang = newLang;
    },
    async loadElements() {
      try {
        this.loading = true;
        let url = '/api/elements';
        
        if (this.viewMode === 'active') {
          url = '/api/elements?archived=false';
        } else if (this.viewMode === 'archived') {
          url = '/api/elements?archived=true';
        }
        // For 'both', don't add archived parameter - API will return all
        
        const response = await axios.get(url);
        this.elements = response.data;
        
        // Load all elements for statistics
        await this.loadAllElementsForStats();
      } catch (error) {
        console.error('Error loading elements:', error);
        alert(this.t('failedLoad'));
      } finally {
        this.loading = false;
      }
    },
    
    async loadAllElementsForStats() {
      try {
        const response = await axios.get('/api/elements');
        this.allElements = response.data;
      } catch (error) {
        console.error('Error loading all elements for stats:', error);
      }
    },
    
    closeAddModal() {
      this.showAddModal = false;
      this.newElement = { title: '', description: '' };
    },
    
    async handleAddElement() {
      try {
        const response = await axios.post('/api/elements', this.newElement);
        this.elements.unshift(response.data);
        this.closeAddModal();
        await this.loadAllElementsForStats();
        await this.loadElements();
      } catch (error) {
        console.error('Error adding element:', error);
        alert(this.t('failedAdd'));
      }
    },
    
    async toggleElement(element) {
      try {
        const response = await axios.put(`/api/elements/${element.id}`, {
          completed: !element.completed
        });
        const index = this.elements.findIndex(e => e.id === element.id);
        if (index !== -1) {
          this.elements[index] = response.data;
        }
        // Update stats
        const allIndex = this.allElements.findIndex(e => e.id === element.id);
        if (allIndex !== -1) {
          this.allElements[allIndex] = response.data;
        }
      } catch (error) {
        console.error('Error toggling element:', error);
        alert(this.t('failedUpdate'));
      }
    },
    
    startEdit(element) {
      this.editingElement = { ...element };
    },
    
    async saveEdit() {
      try {
        const response = await axios.put(`/api/elements/${this.editingElement.id}`, {
          title: this.editingElement.title,
          description: this.editingElement.description
        });
        const index = this.elements.findIndex(e => e.id === this.editingElement.id);
        if (index !== -1) {
          this.elements[index] = response.data;
        }
        // Update stats
        const allIndex = this.allElements.findIndex(e => e.id === this.editingElement.id);
        if (allIndex !== -1) {
          this.allElements[allIndex] = response.data;
        }
        this.editingElement = null;
      } catch (error) {
        console.error('Error updating element:', error);
        alert(this.t('failedUpdate'));
      }
    },
    
    cancelEdit() {
      this.editingElement = null;
    },
    
    async archiveElement(id) {
      if (!confirm(this.t('confirmArchive'))) {
        return;
      }
      
      try {
        await axios.post(`/api/elements/${id}/archive`);
        await this.loadAllElementsForStats();
        await this.loadElements();
      } catch (error) {
        console.error('Error archiving element:', error);
        alert(this.t('failedArchive'));
      }
    },
    
    async restoreElement(id) {
      try {
        await axios.post(`/api/elements/${id}/restore`);
        await this.loadAllElementsForStats();
        await this.loadElements();
      } catch (error) {
        console.error('Error restoring element:', error);
        alert(this.t('failedRestore'));
      }
    },
    
    async removeElement(id) {
      if (!confirm(this.t('confirmRemove'))) {
        return;
      }
      
      try {
        await axios.delete(`/api/elements/${id}/force`);
        await this.loadAllElementsForStats();
        await this.loadElements();
      } catch (error) {
        console.error('Error removing element:', error);
        alert(this.t('failedRemove'));
      }
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString(this.lang === 'ru' ? 'ru-RU' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    handleDragStart(event, index) {
      this.draggingIndex = index;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/html', event.target);
      event.target.style.opacity = '0.5';
    },
    
    handleDragOver(event, index) {
      event.preventDefault();
      event.stopPropagation();
      // Allow dropping at top (-1) or bottom (elements.length) or between elements
      if (this.draggingIndex !== null) {
        // If hovering over a regular element, clear edge positions
        if (index >= 0 && index < this.elements.length) {
          // Special handling for edge positions - clear them when over regular elements
          if (this.dragOverIndex === -1 || this.dragOverIndex === this.elements.length) {
            this.dragOverIndex = null;
          }
        }
        
        // Set the drag over index
        if (index === -1 || index === this.elements.length) {
          this.dragOverIndex = index;
        } else if (this.draggingIndex !== index) {
          this.dragOverIndex = index;
        }
      }
    },
    
    
    handleDragLeave(event) {
      // Only clear if we're actually leaving the element (not just moving to a child)
      if (!event.currentTarget.contains(event.relatedTarget)) {
        // Don't clear immediately, let dragover handle it
      }
    },
    
    handleDrop(event, dropIndex) {
      event.preventDefault();
      event.stopPropagation();
      
      if (this.draggingIndex === null) {
        this.dragOverIndex = null;
        return;
      }
      
      // Don't do anything if dropping on the same position
      if (dropIndex === this.draggingIndex) {
        this.dragOverIndex = null;
        return;
      }
      
      const draggedElement = this.elements[this.draggingIndex];
      const newElements = [...this.elements];
      
      // Remove the dragged element from its original position
      newElements.splice(this.draggingIndex, 1);
      
      // Calculate the correct insertion index
      let insertIndex;
      if (dropIndex === -1) {
        // Moving to the top
        insertIndex = 0;
      } else if (dropIndex === this.elements.length) {
        // Moving to the bottom (after removal, array length is elements.length - 1)
        insertIndex = newElements.length;
      } else {
        // Moving between elements
        // If moving down (draggingIndex < dropIndex), the dropIndex shifts by -1 after removal
        // If moving up (draggingIndex > dropIndex), the dropIndex stays the same
        insertIndex = dropIndex;
        if (this.draggingIndex < dropIndex) {
          insertIndex = dropIndex - 1;
        }
      }
      
      // Insert it at the new position
      newElements.splice(insertIndex, 0, draggedElement);
      
      this.elements = newElements;
      this.dragOverIndex = null;
    },
    
    handleDragEnd(event) {
      event.target.style.opacity = '';
      this.draggingIndex = null;
      this.dragOverIndex = null;
    }
  },
  async mounted() {
    await this.loadAllElementsForStats();
    await this.loadElements();
  },
  watch: {
    showAddModal(newVal) {
      if (newVal) {
        this.$nextTick(() => {
          const input = this.$refs.titleInput;
          if (input) {
            input.focus();
          }
        });
      }
    }
  },
};
</script>

