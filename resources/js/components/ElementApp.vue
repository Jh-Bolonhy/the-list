<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <!-- Authentication Forms -->
      <div v-if="!user" class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <!-- Login Form -->
        <div v-if="!showRegisterForm">
          <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">{{ t('login') }}</h2>
          <form @submit.prevent="handleLogin" class="space-y-4">
            <div>
              <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">{{ t('email') }}</label>
              <input
                v-model="loginForm.email"
                type="email"
                id="login-email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('email')"
                required
              />
            </div>
            <div>
              <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">{{ t('password') }}</label>
              <input
                v-model="loginForm.password"
                type="password"
                id="login-password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('password')"
                required
              />
            </div>
            <button
              type="submit"
              class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 ease-in-out"
            >
              {{ t('login') }}
            </button>
            <p class="text-center text-sm text-gray-600">
              {{ t('dontHaveAccount') }} 
              <button type="button" @click="showRegisterForm = true" class="text-blue-500 hover:underline">
                {{ t('registerHere') }}
              </button>
            </p>
          </form>
        </div>

        <!-- Register Form -->
        <div v-else>
          <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">{{ t('register') }}</h2>
          <form @submit.prevent="handleRegister" class="space-y-4">
            <div>
              <label for="register-name" class="block text-sm font-medium text-gray-700 mb-1">{{ t('name') }}</label>
              <input
                v-model="registerForm.name"
                type="text"
                id="register-name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('name')"
                required
              />
            </div>
            <div>
              <label for="register-email" class="block text-sm font-medium text-gray-700 mb-1">{{ t('email') }}</label>
              <input
                v-model="registerForm.email"
                type="email"
                id="register-email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('email')"
                required
              />
            </div>
            <div>
              <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">{{ t('password') }}</label>
              <input
                v-model="registerForm.password"
                type="password"
                id="register-password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('password')"
                required
                minlength="8"
              />
            </div>
            <div>
              <label for="register-password-confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ t('passwordConfirmation') }}</label>
              <input
                v-model="registerForm.password_confirmation"
                type="password"
                id="register-password-confirmation"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="t('passwordConfirmation')"
                required
                minlength="8"
              />
            </div>
            <button
              type="submit"
              class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 ease-in-out"
            >
              {{ t('register') }}
            </button>
            <p class="text-center text-sm text-gray-600">
              {{ t('alreadyHaveAccount') }} 
              <button type="button" @click="showRegisterForm = false" class="text-blue-500 hover:underline">
                {{ t('loginHere') }}
              </button>
            </p>
          </form>
        </div>
      </div>

      <!-- Main App Content (only shown when authenticated) -->
      <div v-if="user" class="bg-white rounded-lg shadow-lg p-6">
        <div class="mb-4 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <LanguageSwitcher :lang="lang" :t="t" @update:lang="setLang" />
            <div class="text-sm text-gray-600">
              {{ t('loggedInAs') }}: <span class="font-semibold">{{ user.name }}</span>
            </div>
            <button
              @click="handleLogout"
              class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 text-sm transition-all duration-300 ease-in-out"
            >
              {{ t('logout') }}
            </button>
          </div>
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
              class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-12 h-12 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-lg transition-all duration-300 ease-in-out hover:scale-110 flex items-center justify-center z-10"
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

          <div v-else-if="hierarchicalElements.length === 0" class="text-center py-8">
            <p class="text-gray-500">{{ t('noElements') }}</p>
          </div>

          <transition-group 
            v-else 
            name="list" 
            tag="div" 
            class="space-y-3"
            @dragover.prevent="handleGlobalDragOver"
            @drop.prevent="handleGlobalDrop"
          >
            <template v-for="(element, index) in hierarchicalElements" :key="element.id">
              <div
                :draggable="true"
                :data-element-id="element.id"
                @dragstart="handleDragStart($event, index)"
                @dragover.prevent="handleDragOver($event, index)"
                @dragleave="handleDragLeave"
                @drop="handleDrop($event, index)"
                @dragend="handleDragEnd"
                :style="{
                  marginLeft: element.level > 0 ? `${element.level * 20}px` : '0',
                  marginTop: `-${(1 - Math.pow(0.8, element.level + 1)) * 0.75}rem`
                }"
                :class="[
                  getElementClasses(index),
                  'flex items-center p-4 rounded-lg transition-all duration-300 ease-in-out border border-gray-300 cursor-move',
                  element.archived 
                    ? 'bg-gray-200 hover:bg-gray-300' 
                    : 'bg-gray-50 hover:bg-gray-100',
                  draggingIndex === index 
                    ? 'shadow-2xl z-50 scale-[0.98]' 
                    : draggingIndex !== null 
                      ? 'opacity-90' 
                      : '',
                  dragOverIndex === index && draggingIndex !== null && draggingIndex !== index ? 'mt-1' : '',
                  dragOverIndex === index + 1 && draggingIndex !== null && draggingIndex !== index ? 'mb-1' : '',
                  // Visual effects for middle third hover - increase size and add shadow
                  hoverElementIndex === index && hoverElementPart === 'middle' && draggingIndex !== null && draggingIndex !== index
                    ? 'shadow-lg scale-[1.02]' 
                    : ''
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
                      class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm transition-all duration-300 ease-in-out"
                    >
                      {{ t('save') }}
                    </button>
                    <button
                      @click="cancelEdit"
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
                    @click="startEdit(element)"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm transition-all duration-300 ease-in-out"
                  >
                    {{ t('edit') }}
                  </button>
                  <button
                    @click="archiveElement(element.id)"
                    class="px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-sm font-medium transition-all duration-300 ease-in-out"
                  >
                    {{ t('archive') }}
                  </button>
                </template>
                <!-- Actions for archived elements -->
                <template v-else>
                  <button
                    @click="restoreElement(element.id)"
                    class="px-3 py-1 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm transition-all duration-300 ease-in-out"
                  >
                    {{ t('restore') }}
                  </button>
                  <button
                    @click="removeElement(element.id)"
                    class="px-3 py-1 bg-pink-600 text-white rounded-md hover:bg-pink-700 text-sm transition-all duration-300 ease-in-out"
                  >
                    {{ t('remove') }}
                  </button>
                </template>
              </div>
            </div>
            
            </template>
          </transition-group>
        </div>
      </div>
    </div>

    <!-- Add Element Modal -->
    <transition name="modal-fade">
      <div
        v-if="showAddModal"
        class="fixed inset-0 bg-gray-900/70 flex items-center justify-center z-50 transition-opacity duration-300 ease-in-out"
        @click.self="closeAddModal"
      >
        <transition name="modal-scale">
          <div v-if="showAddModal" class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 transition-all duration-300 ease-in-out" @click.stop>
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">{{ t('addNewElement') }}</h2>
          <button
            @click="closeAddModal"
            class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-all duration-300 ease-in-out p-0 m-0"
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
              class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 ease-in-out"
            >
              {{ t('add') }}
            </button>
            <button
              type="button"
              @click="closeAddModal"
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

    <!-- Confirmation Modal -->
    <transition name="modal-fade">
      <div
        v-if="showConfirmModal"
        class="fixed inset-0 bg-gray-900/70 flex items-center justify-center z-50 transition-opacity duration-300 ease-in-out"
        @click.self="closeConfirmModal"
      >
        <transition name="modal-scale">
          <div
            class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 transform transition-all duration-300 ease-in-out"
          >
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ t('confirm') }}</h3>
            <p class="text-gray-600 mb-6">{{ confirmMessage }}</p>
            <div class="flex space-x-3">
              <button
                @click="handleConfirm"
                class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 ease-in-out"
              >
                {{ t('confirm') }}
              </button>
              <button
                @click="closeConfirmModal"
                class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-300 ease-in-out"
              >
                {{ t('cancel') }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
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
      user: null, // Current authenticated user
      showRegisterForm: false, // Show register form instead of login
      loginForm: {
        email: '',
        password: ''
      },
      registerForm: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      elements: [], // Store all elements (used for both display and statistics)
      loading: true,
      viewMode: 'active',
      showAddModal: false,
      newElement: {
        title: '',
        description: ''
      },
      editingElement: null,
      lang: 'en',
      draggingIndex: null,
      dragOverIndex: null,
      hoverElementIndex: null, // Index of element being hovered over
      hoverElementPart: null, // 'upper', 'middle', 'lower', 'above', 'below', 'between'
      mouseY: 0, // Current mouse Y position
      dropZoneElements: [], // Array of element indices in the drop zone
      mousePositionRelativeToCenter: null, // 'above' or 'below' relative to center line between elements
      showConfirmModal: false, // Show confirmation modal
      confirmAction: null, // 'archive' or 'remove'
      confirmMessage: '', // Confirmation message
      pendingElementId: null, // ID of element pending confirmation
    };
  },
  computed: {
    // Filtered elements based on viewMode
    filteredElements() {
      if (this.viewMode === 'active') {
        return this.elements.filter(e => !e.archived);
      } else if (this.viewMode === 'archived') {
        return this.elements.filter(e => e.archived);
      }
      // 'both' - return all elements
      return this.elements;
    },
    activeCount() {
      return this.elements.filter(e => !e.archived).length;
    },
    activeCompletedCount() {
      const active = this.elements.filter(e => !e.archived);
      return active.filter(e => e.completed).length;
    },
    archivedCount() {
      return this.elements.filter(e => e.archived).length;
    },
    // Build hierarchical list: parents first, then their children with indentation
    hierarchicalElements() {
      const result = [];
      const processed = new Set();
      
      // Helper function to add element and its children recursively
      const addElementAndChildren = (element, level = 0) => {
        if (processed.has(element.id)) {
          return;
        }
        
        // Add element with level information
        result.push({
          ...element,
          level: level
        });
        processed.add(element.id);
        
        // Find and add children
        const children = this.filteredElements.filter(e => e.parent_element_id === element.id);
        children.forEach(child => {
          addElementAndChildren(child, level + 1);
        });
      };
      
      // First, add all root elements (those without parent)
      const rootElements = this.filteredElements.filter(e => !e.parent_element_id);
      rootElements.forEach(root => {
        addElementAndChildren(root, 0);
      });
      
      // Then add any remaining elements that might have been missed (orphaned children)
      this.filteredElements.forEach(element => {
        if (!processed.has(element.id)) {
          addElementAndChildren(element, 0);
        }
      });
      
      return result;
    }
  },
  methods: {
    t(key) {
      return locales[this.lang][key] || key;
    },
    setLang(newLang) {
      this.lang = newLang;
    },
    updateCsrfToken(token) {
      // Update meta tag
      const metaTag = document.head.querySelector('meta[name="csrf-token"]');
      if (metaTag) {
        metaTag.setAttribute('content', token);
      }
      // Update axios default header
      window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    },
    async checkAuth() {
      try {
        const response = await axios.get('/api/user');
        this.user = response.data.user;
        if (this.user) {
          await this.loadElements();
        }
      } catch (error) {
        console.error('Error checking auth:', error);
        this.user = null;
      } finally {
        this.loading = false;
      }
    },
    async handleRegister() {
      try {
        const response = await axios.post('/api/register', this.registerForm);
        this.user = response.data.user;
        
        // Update CSRF token if provided
        if (response.data.csrf_token) {
          this.updateCsrfToken(response.data.csrf_token);
        }
        
        this.registerForm = {
          name: '',
          email: '',
          password: '',
          password_confirmation: ''
        };
        await this.loadElements();
      } catch (error) {
        console.error('Error registering:', error);
        if (error.response && error.response.data && error.response.data.errors) {
          const errors = error.response.data.errors;
          const errorMessages = Object.values(errors).flat().join(', ');
          alert(errorMessages);
        } else {
          alert(this.t('failedRegister'));
        }
      }
    },
    async handleLogin() {
      try {
        const response = await axios.post('/api/login', this.loginForm);
        this.user = response.data.user;
        
        // Update CSRF token if provided
        if (response.data.csrf_token) {
          this.updateCsrfToken(response.data.csrf_token);
        }
        
        this.loginForm = {
          email: '',
          password: ''
        };
        await this.loadElements();
      } catch (error) {
        console.error('Error logging in:', error);
        if (error.response && error.response.data && error.response.data.errors) {
          const errors = error.response.data.errors;
          const errorMessages = Object.values(errors).flat().join(', ');
          alert(errorMessages);
        } else {
          alert(this.t('failedLogin'));
        }
      }
    },
    async handleLogout() {
      try {
        const response = await axios.post('/api/logout');
        
        // Update CSRF token if provided
        if (response.data.csrf_token) {
          this.updateCsrfToken(response.data.csrf_token);
        }
        
        this.user = null;
        this.elements = [];
      } catch (error) {
        console.error('Error logging out:', error);
        alert(this.t('failedLogout'));
      }
    },
    async loadElements() {
      try {
        this.loading = true;
        // Always load all elements - filtering is done by computed property
        const response = await axios.get('/api/elements');
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
        const newElement = response.data;
        
        // Add to elements array (filtering is handled by computed property)
        this.elements.unshift(newElement);
        
        this.closeAddModal();
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
        // Update in elements array
        const elementIndex = this.elements.findIndex(e => e.id === this.editingElement.id);
        if (elementIndex !== -1) {
          this.elements[elementIndex] = response.data;
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
    
    archiveElement(id) {
      // Show confirmation modal instead of browser confirm
      this.pendingElementId = id;
      this.confirmAction = 'archive';
      this.confirmMessage = this.t('confirmArchive');
      this.showConfirmModal = true;
    },
    
    async performArchive(id) {
      try {
        await axios.post(`/api/elements/${id}/archive`);
        
        // Update local state for element and all its descendants
        this.updateElementAndDescendants(id, { archived: true });
        // Note: Filtering is handled automatically by filteredElements computed property
      } catch (error) {
        console.error('Error archiving element:', error);
        alert(this.t('failedArchive'));
      }
    },
    
    async restoreElement(id) {
      try {
        await axios.post(`/api/elements/${id}/restore`);
        
        // Update local state for element and all its descendants
        this.updateElementAndDescendants(id, { archived: false });
        // Note: Filtering is handled automatically by filteredElements computed property
      } catch (error) {
        console.error('Error restoring element:', error);
        alert(this.t('failedRestore'));
      }
    },
    
    removeElement(id) {
      // Show confirmation modal instead of browser confirm
      this.pendingElementId = id;
      this.confirmAction = 'remove';
      this.confirmMessage = this.t('confirmRemove');
      this.showConfirmModal = true;
    },
    
    async performRemove(id) {
      try {
        await axios.delete(`/api/elements/${id}/force`);
        
        // Remove from elements array
        const elementIndex = this.elements.findIndex(e => e.id === id);
        if (elementIndex !== -1) {
          this.elements.splice(elementIndex, 1);
        }
      } catch (error) {
        console.error('Error removing element:', error);
        alert(this.t('failedRemove'));
      }
    },
    
    handleConfirm() {
      if (this.pendingElementId === null) {
        this.closeConfirmModal();
        return;
      }
      
      if (this.confirmAction === 'archive') {
        this.performArchive(this.pendingElementId);
      } else if (this.confirmAction === 'remove') {
        this.performRemove(this.pendingElementId);
      }
      
      this.closeConfirmModal();
    },
    
    closeConfirmModal() {
      this.showConfirmModal = false;
      this.confirmAction = null;
      this.confirmMessage = '';
      this.pendingElementId = null;
    },
    
    async setParentElement(elementId, parentId) {
      try {
        // Prevent element from being its own parent
        if (elementId === parentId) {
          return;
        }
        
        await axios.put(`/api/elements/${elementId}`, {
          parent_element_id: parentId
        });
        
        // Update local state instead of reloading
        const elementIndex = this.elements.findIndex(e => e.id === elementId);
        if (elementIndex !== -1) {
          this.elements[elementIndex].parent_element_id = parentId;
        }
      } catch (error) {
        console.error('Error setting parent element:', error);
        alert(this.t('failedUpdate'));
      }
    },
    
    // Determine the parent element ID based on drop position in hierarchical list
    determineParentFromDropPosition(dropIndex) {
      // If dropping at the very beginning (index 0), element becomes root
      if (dropIndex === 0) {
        return null;
      }
      
      // Get elements before and at the drop position
      const previousElement = dropIndex > 0 ? this.hierarchicalElements[dropIndex - 1] : null;
      const nextElement = dropIndex < this.hierarchicalElements.length ? this.hierarchicalElements[dropIndex] : null;
      
      // If dropping at the very end
      if (!nextElement && previousElement) {
        // When dropping at the end, element should always become root
        // regardless of the level of the previous element
        return null;
      }
      
      // If we have both previous and next elements
      if (previousElement && nextElement) {
        const prevLevel = previousElement.level;
        const nextLevel = nextElement.level;
        
        // Case 1: Dropping between root element (level 0) and its child (level 1)
        // Element becomes child of the root element
        if (prevLevel === 0 && nextLevel === 1) {
          return previousElement.id;
        }
        
        // Case 2: Dropping between two children of the same parent (same level > 0)
        // Element becomes child of the same parent
        if (prevLevel === nextLevel && prevLevel > 0) {
          return this.findParentElementId(previousElement.id);
        }
        
        // Case 3: Dropping between parent (level N) and its child (level N+1)
        // Element becomes child of the parent
        if (nextLevel === prevLevel + 1) {
          return previousElement.id;
        }
        
        // Case 4: Dropping between two root elements (both level 0)
        // Element becomes root
        if (prevLevel === 0 && nextLevel === 0) {
          return null;
        }
        
        // Case 5: Dropping between elements where next is at lower level than previous
        // Find the common ancestor or make it root
        if (nextLevel < prevLevel) {
          // Find the element at the same level as next, or make it root
          return nextLevel === 0 ? null : this.findParentElementId(nextElement.id);
        }
      }
      
      // Default: if previous element exists and is not root, use its parent
      if (previousElement && previousElement.level > 0) {
        return this.findParentElementId(previousElement.id);
      }
      
      // Default: element becomes root
      return null;
    },
    
    // Find the parent element ID for a given element
    findParentElementId(elementId) {
      const element = this.elements.find(e => e.id === elementId);
      return element ? element.parent_element_id : null;
    },
    
    // Update element and all its descendants in local state
    updateElementAndDescendants(elementId, updates) {
      // Helper function to recursively update element and children
      const updateRecursive = (id) => {
        // Update in elements array
        const elementIndex = this.elements.findIndex(e => e.id === id);
        if (elementIndex !== -1) {
          Object.assign(this.elements[elementIndex], updates);
        }
        
        // Find and update all children
        const children = this.elements.filter(e => e.parent_element_id === id);
        children.forEach(child => {
          updateRecursive(child.id);
        });
      };
      
      updateRecursive(elementId);
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
    
    getElementClasses(index) {
      // Only apply classes when actually dragging
      if (this.draggingIndex === null) {
        return '';
      }
      
      // The dragging element itself is handled by opacity
      if (index === this.draggingIndex) {
        return '';
      }
      
      const classes = [];
      
      // Check if this element is in the drop zone
      if (this.dropZoneElements.includes(index)) {
        // Determine which margin to increase based on mouse position relative to center
        if (this.hoverElementPart === 'between') {
          // Two elements in drop zone - increase margin for both elements
          if (index === this.hoverElementIndex) {
            // Upper element - increase bottom margin
            classes.push('mb-[30px]');
          } else if (index === this.hoverElementIndex + 1) {
            // Lower element - increase top margin
            classes.push('mt-[30px]');
          }
        } else if (this.hoverElementPart === 'above') {
          // Only first element in drop zone - always increase top margin
          // (mouse is above the element, even if outside the list)
          if (index === 0) {
            classes.push('mt-[30px]'); // Always top margin when mouse is above
          }
        } else if (this.hoverElementPart === 'below') {
          // Only last element in drop zone - always increase bottom margin
          // (mouse is below the element, even if outside the list)
          if (index === this.hierarchicalElements.length - 1) {
            classes.push('mb-[30px]'); // Always bottom margin when mouse is below
          }
        }
      }
      
      return classes.join(' ');
    },
    
    handleDragStart(event, index) {
      this.draggingIndex = index;
      this.dragOverIndex = null;
      this.hoverElementIndex = null;
      this.hoverElementPart = null;
      this.dropZoneElements = [];
      this.mousePositionRelativeToCenter = null;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/html', event.target);
      
      // Add global dragover and drop listeners to track mouse position even outside the list
      document.addEventListener('dragover', this.handleDocumentDragOver);
      document.addEventListener('drop', this.handleDocumentDrop);
    },
    
    handleDocumentDragOver(event) {
      if (this.draggingIndex === null) {
        return;
      }
      
      event.preventDefault(); // Allow drop
      
      // Update mouse position
      this.mouseY = event.clientY;
      
      // Find the list container - try multiple ways
      let listContainer = event.target.closest('.space-y-3');
      if (!listContainer) {
        // Try to find by class in parent
        listContainer = document.querySelector('.space-y-3');
      }
      if (!listContainer) {
        this.dropZoneElements = [];
        this.mousePositionRelativeToCenter = null;
        return;
      }
      
      // Get all draggable elements
      const allElements = listContainer.querySelectorAll('[draggable="true"]');
      if (allElements.length === 0) {
        return;
      }
      
      const mouseY = event.clientY;
      const elementArray = Array.from(allElements);
      
      // Check if mouse is above the first element (above upper third)
      const firstElement = elementArray[0];
      if (firstElement) {
        const firstRect = firstElement.getBoundingClientRect();
        const firstThirdHeight = firstRect.height / 3;
        
        // Check if mouse is above the upper third of the first element
        if (mouseY < firstRect.top + firstThirdHeight) {
          this.hoverElementIndex = 0;
          this.hoverElementPart = 'above';
          this.dragOverIndex = 0;
          this.dropZoneElements = [0]; // First element is in drop zone
          this.mousePositionRelativeToCenter = null;
          return;
        }
      }
      
      // Check if mouse is below the last element (below lower third)
      const lastElement = elementArray[elementArray.length - 1];
      if (lastElement) {
        const lastRect = lastElement.getBoundingClientRect();
        const lastThirdHeight = lastRect.height / 3;
        
        // Check if mouse is below the lower third of the last element
        if (mouseY > lastRect.bottom - lastThirdHeight) {
          this.hoverElementIndex = this.hierarchicalElements.length - 1;
          this.hoverElementPart = 'below';
          this.dragOverIndex = this.hierarchicalElements.length;
          this.dropZoneElements = [this.hierarchicalElements.length - 1]; // Last element is in drop zone
          this.mousePositionRelativeToCenter = null;
          return;
        }
      }
      
      // Check if mouse is between any two elements
      for (let i = 0; i < elementArray.length - 1; i++) {
        const currentElement = elementArray[i];
        const nextElement = elementArray[i + 1];
        
        if (currentElement && nextElement) {
          const currentRect = currentElement.getBoundingClientRect();
          const nextRect = nextElement.getBoundingClientRect();
          
          // Lower third of upper element starts here
          const currentLowerThirdStart = currentRect.bottom - currentRect.height / 3;
          // Upper third of lower element ends here
          const nextUpperThirdEnd = nextRect.top + nextRect.height / 3;
          
          // If mouse is in the extended zone: lower third of upper element, space between, or upper third of lower element
          // Use >= and <= to include the boundaries
          if (mouseY >= currentLowerThirdStart && mouseY <= nextUpperThirdEnd) {
            // Find the actual index in elements array
            const currentElementId = currentElement.getAttribute('data-element-id');
            const currentIndex = this.elements.findIndex(e => e.id.toString() === currentElementId);
            
            if (currentIndex !== -1) {
              this.hoverElementIndex = currentIndex;
              this.hoverElementPart = 'between';
              this.dragOverIndex = currentIndex + 1;
              
              // Set drop zone elements
              this.dropZoneElements = [currentIndex, currentIndex + 1];
              
              // Calculate center line between the two elements
              const centerY = (currentRect.bottom + nextRect.top) / 2;
              
              // Determine mouse position relative to center line
              if (mouseY < centerY) {
                this.mousePositionRelativeToCenter = 'above';
              } else {
                this.mousePositionRelativeToCenter = 'below';
              }
              
              return;
            }
          }
        }
      }
      
      // Check if mouse is in the middle third of any element
      for (let i = 0; i < elementArray.length; i++) {
        const element = elementArray[i];
        if (element) {
          const elementRect = element.getBoundingClientRect();
          const elementHeight = elementRect.height;
          const thirdHeight = elementHeight / 3;
          
          // Check if mouse is in middle third
          const y = mouseY - elementRect.top;
          const isMiddleThird = y >= thirdHeight && y <= (elementHeight - thirdHeight);
          
          if (isMiddleThird) {
            // Find the actual index in elements array
            const elementId = element.getAttribute('data-element-id');
            const elementIndex = this.elements.findIndex(e => e.id.toString() === elementId);
            
            if (elementIndex !== -1 && elementIndex !== this.draggingIndex) {
              // Check if we're not in a 'between' zone
              let isInBetweenZone = false;
              if (i > 0) {
                const prevElement = elementArray[i - 1];
                if (prevElement) {
                  const prevRect = prevElement.getBoundingClientRect();
                  const prevLowerThirdStart = prevRect.bottom - prevRect.height / 3;
                  if (mouseY >= prevLowerThirdStart && mouseY <= elementRect.top + thirdHeight) {
                    isInBetweenZone = true;
                  }
                }
              }
              if (!isInBetweenZone && i < elementArray.length - 1) {
                const nextElement = elementArray[i + 1];
                if (nextElement) {
                  const nextRect = nextElement.getBoundingClientRect();
                  const nextUpperThirdEnd = nextRect.top + nextRect.height / 3;
                  if (mouseY >= elementRect.bottom - thirdHeight && mouseY <= nextUpperThirdEnd) {
                    isInBetweenZone = true;
                  }
                }
              }
              
              if (!isInBetweenZone) {
                this.hoverElementIndex = elementIndex;
                this.hoverElementPart = 'middle';
                this.dragOverIndex = null;
                this.dropZoneElements = [elementIndex];
                this.mousePositionRelativeToCenter = null;
                return;
              }
            }
          }
        }
      }
    },
    
    handleDragOver(event, index) {
      event.preventDefault();
      event.stopPropagation();
      this.mouseY = event.clientY;
      
      if (this.draggingIndex === null) {
        return;
      }
      
      // If hovering over the same element being dragged, ignore
      if (index === this.draggingIndex) {
        this.dragOverIndex = null;
        this.hoverElementIndex = null;
        this.hoverElementPart = null;
        return;
      }
      
      // If hovering over a regular element
      if (index >= 0 && index < this.hierarchicalElements.length) {
        const elementRect = event.currentTarget.getBoundingClientRect();
        const elementHeight = elementRect.height;
        const thirdHeight = elementHeight / 3;
        const mouseY = event.clientY;
        
        // Get all draggable elements to find neighbors
        const allElements = event.currentTarget.parentElement.querySelectorAll('[draggable="true"]');
        const elementArray = Array.from(allElements);
        const currentElementIndex = elementArray.indexOf(event.currentTarget);
        
        // Check if mouse is above upper third of first element - insert at beginning
        if (index === 0 && mouseY < elementRect.top + thirdHeight) {
          this.hoverElementIndex = 0;
          this.hoverElementPart = 'above';
          this.dragOverIndex = 0;
          this.dropZoneElements = [0];
          this.mousePositionRelativeToCenter = null;
          return;
        }
        
        // Check if mouse is below lower third of last element - insert at end
        if (index === this.hierarchicalElements.length - 1 && mouseY > elementRect.bottom - thirdHeight) {
          this.hoverElementIndex = this.hierarchicalElements.length - 1;
          this.hoverElementPart = 'below';
          this.dragOverIndex = this.hierarchicalElements.length;
          this.dropZoneElements = [this.hierarchicalElements.length - 1];
          this.mousePositionRelativeToCenter = null;
          return;
        }
        
        // Check if mouse is in the zone between previous and current element
        // Zone includes: lower third of previous element, space between, and upper third of current element
        if (index > 0 && currentElementIndex > 0) {
          const prevElement = elementArray[currentElementIndex - 1];
          if (prevElement) {
            const prevRect = prevElement.getBoundingClientRect();
            // Lower third of previous element starts here
            const prevLowerThirdStart = prevRect.bottom - prevRect.height / 3;
            // Upper third of current element ends here
            const currentUpperThirdEnd = elementRect.top + thirdHeight;
            
            // If mouse is in the extended zone (lower third of prev, space between, or upper third of current)
            if (mouseY >= prevLowerThirdStart && mouseY <= currentUpperThirdEnd) {
              this.hoverElementIndex = index - 1;
              this.hoverElementPart = 'between';
              this.dragOverIndex = index;
              
              // Set drop zone elements
              this.dropZoneElements = [index - 1, index];
              
              // Calculate center line between the two elements
              const centerY = (prevRect.bottom + elementRect.top) / 2;
              
              // Determine mouse position relative to center line
              if (mouseY < centerY) {
                this.mousePositionRelativeToCenter = 'above';
              } else {
                this.mousePositionRelativeToCenter = 'below';
              }
              
              return;
            }
          }
        }
        
        // Check if mouse is in the zone between current and next element
        // Zone includes: lower third of current element, space between, and upper third of next element
        if (index < this.hierarchicalElements.length - 1 && currentElementIndex < elementArray.length - 1) {
          const nextElement = elementArray[currentElementIndex + 1];
          if (nextElement) {
            const nextRect = nextElement.getBoundingClientRect();
            // Lower third of current element starts here
            const currentLowerThirdStart = elementRect.bottom - thirdHeight;
            // Upper third of next element ends here
            const nextUpperThirdEnd = nextRect.top + nextRect.height / 3;
            
            // If mouse is in the extended zone (lower third of current, space between, or upper third of next)
            if (mouseY >= currentLowerThirdStart && mouseY <= nextUpperThirdEnd) {
              this.hoverElementIndex = index;
              this.hoverElementPart = 'between';
              this.dragOverIndex = index + 1;
              
              // Set drop zone elements
              this.dropZoneElements = [index, index + 1];
              
              // Calculate center line between the two elements
              const centerY = (elementRect.bottom + nextRect.top) / 2;
              
              // Determine mouse position relative to center line
              if (mouseY < centerY) {
                this.mousePositionRelativeToCenter = 'above';
              } else {
                this.mousePositionRelativeToCenter = 'below';
              }
              
              return;
            }
          }
        }
        
        // If mouse is in middle third and not in between-elements zone, show visual effect but don't allow drop
        const y = mouseY - elementRect.top;
        const isMiddleThird = y >= thirdHeight && y <= (elementHeight - thirdHeight);
        if (isMiddleThird) {
          this.hoverElementIndex = index;
          this.hoverElementPart = 'middle';
          this.dragOverIndex = null;
          this.dropZoneElements = [];
          this.mousePositionRelativeToCenter = null;
          return;
        }
        
        // Default fallback: if in upper third, insert before; if in lower third, insert after
        const isUpperThird = y < thirdHeight;
        if (isUpperThird) {
          this.hoverElementIndex = index;
          this.hoverElementPart = 'upper';
          this.dragOverIndex = index;
          this.dropZoneElements = [];
          this.mousePositionRelativeToCenter = null;
        } else {
          this.hoverElementIndex = index;
          this.hoverElementPart = 'lower';
          this.dragOverIndex = index + 1;
          this.dropZoneElements = [];
          this.mousePositionRelativeToCenter = null;
        }
      }
    },
    
    
    handleDragLeave(event) {
      // Only clear if we're actually leaving the element (not just moving to a child)
      if (!event.currentTarget.contains(event.relatedTarget)) {
        // Clear drop zone when leaving the element
        // The dragover handler will update it if we're still in a valid zone
        this.dropZoneElements = [];
        this.mousePositionRelativeToCenter = null;
      }
    },
    
    async handleDrop(event, dropIndex) {
      event.preventDefault();
      event.stopPropagation();
      
      if (this.draggingIndex === null) {
        this.dragOverIndex = null;
        return;
      }
      
      // If dragOverIndex is null but hoverElementPart is 'middle', make it a child
      if (this.dragOverIndex === null) {
        // Check if we're dropping on middle third of an element (making it a child)
        if (this.hoverElementPart === 'middle' && this.hoverElementIndex !== null) {
          const draggedElement = this.hierarchicalElements[this.draggingIndex];
          const parentElement = this.hierarchicalElements[this.hoverElementIndex];
          
          // Prevent element from being its own parent or child
          if (draggedElement.id === parentElement.id) {
            this.dragOverIndex = null;
            return;
          }
          
          // Update parent_element_id via API and update local state
          try {
            await axios.put(`/api/elements/${draggedElement.id}`, {
              parent_element_id: parentElement.id
            });
            
            // Update local state instead of reloading
            const elementIndex = this.elements.findIndex(e => e.id === draggedElement.id);
            if (elementIndex !== -1) {
              this.elements[elementIndex].parent_element_id = parentElement.id;
            }
          } catch (error) {
            console.error('Error setting parent element:', error);
            alert(this.t('failedUpdate'));
          }
          this.dragOverIndex = null;
          return;
        }
        // Otherwise, it's an invalid drop
        return;
      }
      
      // Use dragOverIndex instead of dropIndex for accurate positioning
      const actualDropIndex = this.dragOverIndex;
      
      // If dropping back at the original position (between the same neighbors), don't move
      // Original position is between draggingIndex and draggingIndex + 1
      // Exception: if moving to the very bottom (elements.length), allow it even if it's draggingIndex + 1
      // because the element might not be the last one
      if (actualDropIndex === this.draggingIndex) {
        // Dropping at the same position
        this.dragOverIndex = null;
        return;
      }
      
      // If dropping at draggingIndex + 1 and it's not the bottom position, it's the original position
      if (actualDropIndex === this.draggingIndex + 1 && actualDropIndex !== this.hierarchicalElements.length) {
        // Element returns to its original position - no change needed
        this.dragOverIndex = null;
        return;
      }
      
      const draggedElement = this.hierarchicalElements[this.draggingIndex];
      // For reordering, we need to work with the original elements array
      // Find the original element in the elements array
      const originalElement = this.elements.find(e => e.id === draggedElement.id);
      if (!originalElement) {
        this.dragOverIndex = null;
        return;
      }
      
      // Determine the new parent based on drop position
      const newParentId = this.determineParentFromDropPosition(actualDropIndex);
      
      // If parent changed, update it via API and update local state
      if (originalElement.parent_element_id !== newParentId) {
        try {
          await axios.put(`/api/elements/${originalElement.id}`, {
            parent_element_id: newParentId
          });
          
          // Update local state instead of reloading
          const elementIndex = this.elements.findIndex(e => e.id === originalElement.id);
          if (elementIndex !== -1) {
            this.elements[elementIndex].parent_element_id = newParentId;
          }
        } catch (error) {
          console.error('Error setting parent element:', error);
          alert(this.t('failedUpdate'));
        }
        this.dragOverIndex = null;
        return;
      }
      
      const newElements = [...this.elements];
      const originalIndex = this.elements.findIndex(e => e.id === draggedElement.id);
      
      // Remove the dragged element from its original position
      newElements.splice(originalIndex, 1);
      
      // Calculate the correct insertion index using actualDropIndex
      // We need to find the target element in the original elements array
      let insertIndex;
      if (actualDropIndex === this.hierarchicalElements.length) {
        // Moving to the bottom (after removal, array length is elements.length - 1)
        insertIndex = newElements.length;
      } else {
        // Moving between elements
        // Find the target element in hierarchicalElements and then find it in original elements
        const targetElement = this.hierarchicalElements[actualDropIndex];
        if (targetElement) {
          const targetOriginalIndex = this.elements.findIndex(e => e.id === targetElement.id);
          if (targetOriginalIndex !== -1) {
            insertIndex = targetOriginalIndex;
            if (originalIndex < targetOriginalIndex) {
              insertIndex = targetOriginalIndex - 1;
            }
          } else {
            insertIndex = newElements.length;
          }
        } else {
          insertIndex = newElements.length;
        }
      }
      
      // Insert it at the new position
      newElements.splice(insertIndex, 0, originalElement);
      
      this.elements = newElements;
      this.dragOverIndex = null;
    },
    
    handleDragEnd(event) {
      // Remove global listeners
      document.removeEventListener('dragover', this.handleDocumentDragOver);
      document.removeEventListener('drop', this.handleDocumentDrop);
      
      this.draggingIndex = null;
      this.dragOverIndex = null;
      this.hoverElementIndex = null;
      this.hoverElementPart = null;
      this.dropZoneElements = [];
      this.mousePositionRelativeToCenter = null;
      this.mouseY = 0;
    },
    
    handleGlobalDragOver(event) {
      // This handles dragover on the list container itself (between elements)
      this.handleDocumentDragOver(event);
    },
    
    async handleGlobalDrop(event) {
      // Handle drop on the list container (between elements or outside)
      event.preventDefault();
      event.stopPropagation();
      
      if (this.draggingIndex === null) {
        return;
      }
      
      // Update dragOverIndex before calling handleDrop
      // This ensures we have the correct position even if dragover wasn't called recently
      this.handleDocumentDragOver(event);
      
      // If dragOverIndex is still null, try to determine position from mouse coordinates
      if (this.dragOverIndex === null) {
        const listContainer = event.currentTarget;
        const allElements = listContainer.querySelectorAll('[draggable="true"]');
        
        if (allElements.length > 0) {
          const mouseY = event.clientY;
          const firstElement = allElements[0];
          const lastElement = allElements[allElements.length - 1];
          
          if (firstElement) {
            const firstRect = firstElement.getBoundingClientRect();
            const firstThirdHeight = firstRect.height / 3;
            if (mouseY < firstRect.top + firstThirdHeight) {
              this.dragOverIndex = 0;
              this.hoverElementIndex = 0;
              this.hoverElementPart = 'above';
            }
          }
          
          if (lastElement && this.dragOverIndex === null) {
            const lastRect = lastElement.getBoundingClientRect();
            const lastThirdHeight = lastRect.height / 3;
            if (mouseY > lastRect.bottom - lastThirdHeight) {
              this.dragOverIndex = this.hierarchicalElements.length;
              this.hoverElementIndex = this.hierarchicalElements.length - 1;
              this.hoverElementPart = 'below';
            }
          }
        }
      }
      
      // Check if we're dropping on middle third (making it a child)
      if (this.dragOverIndex === null && this.hoverElementPart === 'middle' && this.hoverElementIndex !== null) {
        const draggedElement = this.hierarchicalElements[this.draggingIndex];
        const parentElement = this.hierarchicalElements[this.hoverElementIndex];
        
        // Prevent element from being its own parent or child
        if (draggedElement.id === parentElement.id) {
          this.dragOverIndex = null;
          return;
        }
        
        // Update parent_element_id via API and update local state
        try {
          await axios.put(`/api/elements/${draggedElement.id}`, {
            parent_element_id: parentElement.id
          });
          
          // Update local state instead of reloading
          const elementIndex = this.elements.findIndex(e => e.id === draggedElement.id);
          if (elementIndex !== -1) {
            this.elements[elementIndex].parent_element_id = parentElement.id;
          }
        } catch (error) {
          console.error('Error setting parent element:', error);
          alert(this.t('failedUpdate'));
        }
        this.dragOverIndex = null;
        return;
      }
      
      // Use the same drop logic as regular handleDrop
      this.handleDrop(event, null);
    },
    
    async handleDocumentDrop(event) {
      // Handle drop anywhere on the document (even outside the list)
      if (this.draggingIndex === null) {
        return;
      }
      
      // Only process if this is related to our drag operation
      // Check if we're dropping near the list
      const listContainer = document.querySelector('.space-y-3');
      if (!listContainer) {
        return;
      }
      
      event.preventDefault();
      event.stopPropagation();
      
      // Update dragOverIndex based on mouse position
      this.handleDocumentDragOver(event);
      
      // If dragOverIndex is still null, try to determine position from mouse coordinates
      if (this.dragOverIndex === null) {
        const allElements = listContainer.querySelectorAll('[draggable="true"]');
        
        if (allElements.length > 0) {
          const mouseY = event.clientY;
          const firstElement = allElements[0];
          const lastElement = allElements[allElements.length - 1];
          
          if (firstElement) {
            const firstRect = firstElement.getBoundingClientRect();
            const firstThirdHeight = firstRect.height / 3;
            // If mouse is above the upper third of first element (even if outside visual bounds)
            if (mouseY < firstRect.top + firstThirdHeight) {
              this.dragOverIndex = 0;
              this.hoverElementIndex = 0;
              this.hoverElementPart = 'above';
            }
          }
          
          if (lastElement && this.dragOverIndex === null) {
            const lastRect = lastElement.getBoundingClientRect();
            const lastThirdHeight = lastRect.height / 3;
            // If mouse is below the lower third of last element (even if outside visual bounds)
            if (mouseY > lastRect.bottom - lastThirdHeight) {
              this.dragOverIndex = this.hierarchicalElements.length;
              this.hoverElementIndex = this.hierarchicalElements.length - 1;
              this.hoverElementPart = 'below';
            }
          }
        }
      }
      
      // Use the same drop logic as regular handleDrop
      if (this.dragOverIndex !== null) {
        await this.handleDrop(event, null);
      }
    }
  },
  async mounted() {
    await this.checkAuth();
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

<style scoped>
/* Modal fade transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease-in-out;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Modal scale transition */
.modal-scale-enter-active,
.modal-scale-leave-active {
  transition: all 0.3s ease-in-out;
}

.modal-scale-enter-from {
  opacity: 0;
  transform: scale(0.9);
}

.modal-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* List transition for smooth element reordering */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease-in-out;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.list-leave-active {
  position: absolute;
  width: 100%;
}
</style>

