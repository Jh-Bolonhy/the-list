<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ t('elementList') }}</h1>
        <LanguageSwitcher :lang="lang" :t="t" @update:lang="setLang" />

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
            <div
              v-for="element in elements"
              :key="element.id"
              :class="[
                'flex items-center p-4 rounded-lg transition-colors',
                element.archived 
                  ? 'bg-gray-200 hover:bg-gray-300' 
                  : 'bg-gray-50 hover:bg-gray-100'
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
    };
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
      } catch (error) {
        console.error('Error loading elements:', error);
        alert(this.t('failedLoad'));
      } finally {
        this.loading = false;
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
        await this.loadElements();
      } catch (error) {
        console.error('Error archiving element:', error);
        alert(this.t('failedArchive'));
      }
    },
    
    async restoreElement(id) {
      try {
        await axios.post(`/api/elements/${id}/restore`);
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
    }
  },
  async mounted() {
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

