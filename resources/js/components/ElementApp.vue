<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ t('elementList') }}</h1>
        <LanguageSwitcher :lang="lang" :t="t" @update:lang="setLang" />
        
        <!-- Add Element Form (only show for active elements) -->
        <div v-if="!showArchived" class="mb-8 p-4 bg-gray-50 rounded-lg">
          <h2 class="text-xl font-semibold mb-4">{{ t('addNewElement') }}</h2>
          <form @submit.prevent="addElement" class="space-y-4">
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ t('title') }}</label>
              <input
                v-model="newElement.title"
                type="text"
                id="title"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('title')"
                required
              />
            </div>
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ t('description') }}</label>
              <textarea
                v-model="newElement.description"
                id="description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('description') + ' (' + t('optional') + ')'"
              ></textarea>
            </div>
            <button
              type="submit"
              class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
              {{ t('add') }}
            </button>
          </form>
        </div>

        <!-- Element List -->
        <div class="space-y-4">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">{{ t('yourElements') }}</h2>
            <button
              @click="toggleArchivedView"
              :class="[
                'px-4 py-2 text-white rounded-md text-sm transition-colors',
                showArchived 
                  ? 'bg-green-500 hover:bg-green-600' 
                  : 'bg-gray-500 hover:bg-gray-600'
              ]"
            >
              {{ showArchived ? t('showActive') : t('showArchived') }}
            </button>
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
              class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
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
                <template v-if="!showArchived">
                  <button
                    @click="startEdit(element)"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm"
                  >
                    {{ t('edit') }}
                  </button>
                  <button
                    @click="archiveElement(element.id)"
                    class="px-3 py-1 bg-orange-400 text-white rounded-md hover:bg-orange-500 text-sm"
                  >
                    {{ t('archive') }}
                  </button>
                </template>
                <!-- Actions for archived elements -->
                <template v-else>
                  <button
                    @click="restoreElement(element.id)"
                    class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm"
                  >
                    {{ t('restore') }}
                  </button>
                  <button
                    @click="removeElement(element.id)"
                    class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 text-sm"
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
      showArchived: false,
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
        const url = this.showArchived 
          ? '/api/elements?archived=true' 
          : '/api/elements';
        const response = await axios.get(url);
        this.elements = response.data;
      } catch (error) {
        console.error('Error loading elements:', error);
        alert(this.t('failedLoad'));
      } finally {
        this.loading = false;
      }
    },
    
    toggleArchivedView() {
      this.showArchived = !this.showArchived;
      this.loadElements();
    },
    
    async addElement() {
      try {
        const response = await axios.post('/api/elements', this.newElement);
        this.elements.unshift(response.data);
        this.newElement = { title: '', description: '' };
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
};
</script>

