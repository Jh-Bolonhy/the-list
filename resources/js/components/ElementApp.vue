<template>
  <div class="min-h-screen py-8" style="background-color: #4F46E5;">
    <div class="max-w-4xl mx-auto px-4 relative main-content-wrapper">
      <!-- Large Icon on the left -->
      <div v-if="user" class="absolute -left-24 top-6 hidden xl:block flex items-center" style="height: 48px;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 450" width="48" height="48" class="opacity-20">
          <!-- Letter T (top) -->
          <!-- Horizontal bar of T -->
          <line x1="70.77505" y1="62.2498" x2="396.49999" y2="62.2498" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
          <!-- Vertical bar of T -->
          <line x1="148.77505" y1="72.2498" x2="147.77505" y2="292.24979" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
          
          <!-- List items between T and L -->
          <line x1="214.77505" y1="144.2498" x2="394.77506" y2="144.2498" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
          <line x1="271.77505" y1="209.2498" x2="393.77505" y2="209.2498" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
          <line x1="271.77506" y1="278.2498" x2="392.77506" y2="278.2498" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
          
          <!-- Letter L (bottom) -->
          <!-- Vertical bar of L -->
          <line x1="70.77505" y1="348.24979" x2="68.77505" y2="133.2498" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
          <!-- Horizontal bar of L -->
          <line x1="70.77505" y1="357.24982" x2="392.77506" y2="357.24982" 
                stroke="white" 
                stroke-width="20" 
                stroke-linecap="round"/>
        </svg>
      </div>

      <!-- Authentication Forms -->
      <AuthForms
        v-if="!user"
        :show-register-form="showRegisterForm"
        :login-form="loginForm"
        :register-form="registerForm"
        :t="t"
        @login="handleLogin"
        @register="handleRegister"
        @show-register="showRegisterForm = true"
        @show-login="showRegisterForm = false"
      />

      <!-- Main App Content (only shown when authenticated) -->
      <div v-if="user" class="bg-white rounded-lg shadow-lg p-6 white-background-container">
        <!-- Header -->
        <AppHeader
          :user="user"
          v-model:headline="headline"
          :headline-display="getHeaderDisplay()"
          :is-editing-headline="isEditingHeadline"
          :initial-headline-width="initialHeadlineWidth"
          :max-headline-width="maxHeadlineWidth"
          :view-mode="viewMode"
          :active-count="activeCount"
          :active-completed-count="activeCompletedCount"
          :archived-count="archivedCount"
          :lang="lang"
          :t="t"
          ref="headerRow"
          @start-editing="startEditingHeadline"
          @headline-input="checkHeadlineWidth"
          @finish-editing="finishEditingHeadline"
          @cancel-editing="cancelEditingHeadline"
          @show-add-modal="showAddModal = true"
          @view-mode-change="setViewMode($event)"
          @lang-change="setLang($event)"
          @logout="handleLogout"
        />

        <!-- Element List -->
        <div class="space-y-4 element-list-container">

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
            class="space-y-3 list-container"
            @dragover.prevent="handleGlobalDragOver"
            @drop.prevent="handleGlobalDrop"
            @before-leave="onBeforeLeave"
          >
            <ElementItem
              v-for="(element, index) in hierarchicalElements"
              :key="element.id"
              :element="element"
              :index="index"
              :editing-element="editingElement"
              :dragging-index="draggingIndex"
              :drag-over-index="dragOverIndex"
              :hover-element-index="hoverElementIndex"
              :hover-element-part="hoverElementPart"
              :element-classes="getElementClasses(index)"
              :t="t"
              :format-date="formatDate"
              @drag-start="handleDragStart"
              @drag-over="handleDragOver"
              @drag-leave="handleDragLeave"
              @drop="handleDrop"
              @drag-end="handleDragEnd"
              @toggle="toggleElement"
              @start-edit="startEdit"
              @save-edit="saveEdit"
              @cancel-edit="cancelEdit"
              @archive="archiveElement"
              @restore="restoreElement"
              @remove="removeElement"
              @toggle-collapse="toggleCollapse"
              @toggle-lock="toggleLock"
              :has-children="hasChildren(element.id)"
              :is-collapsed="collapsedElements[element.id]"
              :locked-element-id="lockedElementId"
            />
          </transition-group>
        </div>
      </div>
    </div>

    <!-- Add Element Modal -->
    <AddElementModal
      :show="showAddModal"
      :t="t"
      @close="closeAddModal"
      @submit="handleAddElement"
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      :message="confirmMessage"
      :t="t"
      @close="closeConfirmModal"
      @confirm="handleConfirm"
    />
  </div>
</template>

<script>
import axios from 'axios';
import en from '../lang/en.js';
import ru from '../lang/ru.js';
const locales = { en, ru };

import AuthForms from './auth/AuthForms.vue';
import AppHeader from './AppHeader.vue';
import AddElementModal from './modals/AddElementModal.vue';
import ConfirmModal from './modals/ConfirmModal.vue';
import ElementItem from './elements/ElementItem.vue';

export default {
  name: 'ElementApp',
  components: {
    AuthForms,
    AppHeader,
    AddElementModal,
    ConfirmModal,
    ElementItem
  },
  data() {
    return {
      user: null, // Current authenticated user
      headline: '', // Headline for display (max width: 1/3 of row)
      isEditingHeadline: false, // Whether headline is being edited
      maxHeadlineWidth: 0, // Maximum allowed width for headline (1/3 of row width)
      pendingCursorPosition: undefined, // Cursor position to set after input is rendered
      initialHeadlineWidth: null, // Initial width of input based on current text
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
      viewMode: localStorage.getItem('viewMode') || 'active', // Load from localStorage, default to 'active'
      showAddModal: false,
      editingElement: null,
      lang: localStorage.getItem('lang') || 'en', // Load from localStorage, default to 'en'
      draggingIndex: null,
      dragOverIndex: null,
      hoverElementIndex: null, // Index of element being hovered over
      hoverElementPart: null, // 'upper', 'middle', 'lower', 'above', 'below', 'between'
      mouseY: 0, // Current mouse Y position
      dropZoneElements: [], // Array of element indices in the drop zone
      dragOverTimeout: null, // Timeout for debouncing dragOver updates
      showConfirmModal: false, // Show confirmation modal
      confirmAction: null, // 'archive' or 'remove'
      confirmMessage: '', // Confirmation message
      pendingElementId: null, // ID of element pending confirmation
      collapsedElements: {}, // Object mapping element IDs to collapse state (reactive)
      lockedElementId: null, // Only one locked parent per user; when set, show only it + descendants
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
      const lockedId = this.lockedElementId;
      const byId = new Map(this.filteredElements.map(e => [e.id, e]));

      // Helper function to check if any parent is collapsed
      const isAnyParentCollapsed = (elementId) => {
        if (lockedId && elementId === lockedId) {
          return false; // locked root should not be affected by ancestors outside the subtree
        }
        const element = byId.get(elementId);
        if (!element || !element.parent_element_id) {
          return false;
        }
        // Stop collapse propagation above the locked root
        if (lockedId && element.parent_element_id === lockedId) {
          return !!this.collapsedElements[lockedId];
        }
        if (this.collapsedElements[element.parent_element_id]) {
          return true;
        }
        return isAnyParentCollapsed(element.parent_element_id);
      };

      // Helper function to add element and its children recursively
      const addElementAndChildren = (element, level = 0) => {
        if (processed.has(element.id)) {
          return;
        }

        // Skip element if any of its parents is collapsed
        if (isAnyParentCollapsed(element.id)) {
          return;
        }

        // Add element with level information
        result.push({
          ...element,
          level: level
        });
        processed.add(element.id);

        // Only add children if element is not collapsed
        if (!this.collapsedElements[element.id]) {
          // Find and add children, sorted by order
          const children = this.filteredElements
            .filter(e => e.parent_element_id === element.id)
            .sort((a, b) => (a.order || 0) - (b.order || 0));
          children.forEach(child => {
            addElementAndChildren(child, level + 1);
          });
        }
      };

      // If a lock is active, show only the locked element and its descendants (levels start at 0)
      if (lockedId) {
        const lockedElement = byId.get(lockedId);
        if (lockedElement) {
          addElementAndChildren(lockedElement, 0);
          return result;
        }
      }

      // First, add all root elements (those without parent), sorted by order
      const rootElements = this.filteredElements
        .filter(e => !e.parent_element_id)
        .sort((a, b) => (a.order || 0) - (b.order || 0));
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
    /**
     * Handle errors with optional reload and alert
     * @param {Error} error - The error object
     * @param {string} messageKey - Translation key for error message
     * @param {boolean} reload - Whether to reload elements on error
     */
    async handleError(error, messageKey, reload = true) {
      console.error(`Error: ${messageKey}`, error);
      if (reload) {
        await this.loadElements();
      }
      alert(this.t(messageKey));
    },
    /**
     * Sync locale between localStorage and database
     * @param {string} currentLang - Current locale from localStorage
     */
    async syncLocale(currentLang) {
      if (this.user.locale && this.user.locale !== this.lang) {
        // User has different locale in DB - sync from DB (another device changed it)
        this.lang = this.user.locale;
        localStorage.setItem('lang', this.user.locale);
      } else if (!this.user.locale) {
        // User doesn't have locale in DB - save current localStorage value
        this.lang = currentLang;
        try {
          await axios.put('/api/user/locale', { locale: currentLang });
          this.user.locale = currentLang;
        } catch (error) {
          console.error('Error saving locale to database:', error);
          // Not critical - continue with localStorage value
        }
      }
    },
    /**
     * Sync show_mode between localStorage and database
     * @param {string} currentViewMode - Current view mode from localStorage
     */
    async syncShowMode(currentViewMode) {
      if (this.user.show_mode && this.user.show_mode !== this.viewMode) {
        // User has different show_mode in DB - sync from DB (another device changed it)
        this.viewMode = this.user.show_mode;
        localStorage.setItem('viewMode', this.user.show_mode);
      } else if (!this.user.show_mode) {
        // User doesn't have show_mode in DB - save current localStorage value
        this.viewMode = currentViewMode;
        try {
          await axios.put('/api/user/show-mode', { show_mode: currentViewMode });
          this.user.show_mode = currentViewMode;
        } catch (error) {
          console.error('Error saving show_mode to database:', error);
          // Not critical - continue with localStorage value
        }
      }
    },
    async setLang(newLang) {
      this.lang = newLang;
      // Always save to localStorage (works for both authenticated and non-authenticated users)
      localStorage.setItem('lang', newLang);

      // If authenticated, also save to database for synchronization
      if (this.user) {
        try {
          await axios.put('/api/user/locale', { locale: newLang });
          // Update user object with new locale
          this.user.locale = newLang;
        } catch (error) {
          console.error('Error saving locale to database:', error);
          // Not critical - continue with localStorage value
        }
      }
    },
    async setViewMode(newViewMode) {
      this.viewMode = newViewMode;
      // Always save to localStorage (works for both authenticated and non-authenticated users)
      localStorage.setItem('viewMode', newViewMode);

      // If authenticated, also save to database for synchronization
      if (this.user) {
        try {
          await axios.put('/api/user/show-mode', { show_mode: newViewMode });
          // Update user object with new show_mode
          this.user.show_mode = newViewMode;
        } catch (error) {
          console.error('Error saving show_mode to database:', error);
          // Not critical - continue with localStorage value
        }
      }

      // Reload elements after view mode change
      await this.loadElements();
    },
    getHeaderDisplay() {
      // If user is not logged in, show default
      if (!this.user) {
        return this.t('defaultHeader');
      }

      // If headline is set and not empty, show it
      if (this.headline && this.headline.trim() !== '') {
        return this.headline;
      }

      // Otherwise show default
      return this.t('defaultHeader');
    },
    updateMaxHeadlineWidth() {
      const headerRow = this.$refs.headerRow?.$el || this.$refs.headerRow;
      if (headerRow) {
        const rowWidth = headerRow.offsetWidth;
        this.maxHeadlineWidth = rowWidth / 3;
      }
    },
    measureTextWidth(text, font) {
      // Create a temporary canvas element to measure text width
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      context.font = font;
      return context.measureText(text).width;
    },
    checkHeadlineWidth() {
      const headerRow = this.$refs.headerRow?.$el || this.$refs.headerRow;
      if (!headerRow) {
        return;
      }

      // Get the row width and calculate 1/3
      const rowWidth = headerRow.offsetWidth;
      const maxWidth = rowWidth / 3;
      this.maxHeadlineWidth = maxWidth;

      // Get the computed font style from the input (try to get from AppHeader component)
      const headlineInput = this.$refs.headerRow?.$refs?.headlineInputRef;
      if (!headlineInput) {
        return;
      }

      const computedStyle = window.getComputedStyle(headlineInput);
      const font = `${computedStyle.fontWeight} ${computedStyle.fontSize} ${computedStyle.fontFamily}`;

      // Measure the current text width
      const textWidth = this.measureTextWidth(this.headline, font);

      // Update input width to match text width (but not exceed maxWidth)
      if (textWidth > maxWidth) {
        // If text exceeds max width, trim it
        let trimmedText = this.headline;
        while (trimmedText.length > 0 && this.measureTextWidth(trimmedText, font) > maxWidth) {
          trimmedText = trimmedText.slice(0, -1);
        }
        this.headline = trimmedText;
        this.initialHeadlineWidth = maxWidth;
      } else {
        // Update width to match current text width
        this.initialHeadlineWidth = textWidth;
      }
    },
    startEditingHeadline(event) {
      if (!this.user) {
        return;
      }

      // Store click position and get h1 element info BEFORE switching to input
      const clickX = event.clientX;
      const h1Element = event.currentTarget;
      const h1Rect = h1Element.getBoundingClientRect();
      const relativeX = clickX - h1Rect.left;
      const headlineText = this.getHeaderDisplay();

      // Get the computed font style from h1 element (before it disappears)
      const h1Style = window.getComputedStyle(h1Element);
      const font = `${h1Style.fontWeight} ${h1Style.fontSize} ${h1Style.fontFamily}`;

      // Calculate the width of the current text to set as initial input width
      const textWidth = this.measureTextWidth(headlineText, font);
      this.initialHeadlineWidth = textWidth;

      // Find the character position at the click location using h1 styles
      let cursorPosition = headlineText.length; // Default to end
      let currentWidth = 0;

      for (let i = 0; i < headlineText.length; i++) {
        const charWidth = this.measureTextWidth(headlineText[i], font);
        // Check if click is before the middle of this character
        if (currentWidth + charWidth / 2 > relativeX) {
          cursorPosition = i;
          break;
        }
        currentWidth += charWidth;
        cursorPosition = i + 1;
      }

      // Store cursor position for use after input is rendered
      this.pendingCursorPosition = cursorPosition;

      this.isEditingHeadline = true;
      // Focus the input in the next tick to ensure it's rendered
      this.$nextTick(() => {
        const headerRow = this.$refs.headerRow?.$el || this.$refs.headerRow;
        const input = this.$refs.headerRow?.$refs?.headlineInputRef;
        if (input) {
          // Calculate max width before focusing
          if (headerRow) {
            const rowWidth = headerRow.offsetWidth;
            this.maxHeadlineWidth = rowWidth / 3;
          }

          input.focus();
          // Set cursor position without selecting text
          if (this.pendingCursorPosition !== undefined) {
            const pos = Math.min(this.pendingCursorPosition, this.headline.length);
            input.setSelectionRange(pos, pos);
            this.pendingCursorPosition = undefined;
          }
        }
      });
    },
    async finishEditingHeadline() {
      if (!this.user) {
        return;
      }

      // Check width one more time before saving
      this.checkHeadlineWidth();

      // Save to database
      await this.updateHeadline();

      // Stop editing
      this.isEditingHeadline = false;
      this.initialHeadlineWidth = null; // Reset width
    },
    cancelEditingHeadline() {
      if (!this.user) {
        return;
      }

      // Restore original value from user object
      const rawHeadline = this.user.headline !== null && this.user.headline !== undefined ? this.user.headline : '';
      this.headline = rawHeadline;

      // Stop editing
      this.isEditingHeadline = false;
      this.initialHeadlineWidth = null; // Reset width
    },
    async updateHeadline() {
      if (!this.user) {
        return;
      }

      // Check width before saving
      this.checkHeadlineWidth();

      // Save current value before API call (in case of error)
      const previousValue = this.user.headline !== null && this.user.headline !== undefined ? this.user.headline : '';

      try {
        const response = await axios.put('/api/user/headline', {
          headline: this.headline || null
        });

        // Update user object with new headline from server
        if (response.data.user) {
          this.user.headline = response.data.user.headline;
          // Ensure headline matches server value
          const rawHeadline = this.user.headline !== null && this.user.headline !== undefined ? this.user.headline : '';
          this.headline = rawHeadline;
        }
      } catch (error) {
        console.error('Error updating headline:', error);
        // Revert to original value on error
        this.headline = previousValue;
      }
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
          // Restore lock state from server
          this.lockedElementId = this.user.locked_element_id ?? null;

          // Initialize headline from headline (or empty string if null)
          // Width limitation is handled dynamically by checkHeadlineWidth() method
          const rawHeadline = this.user.headline !== null && this.user.headline !== undefined ? this.user.headline : '';
          this.headline = rawHeadline;

          // Sync locale and show_mode
          const currentLang = localStorage.getItem('lang') || 'en';
          await this.syncLocale(currentLang);

          const currentViewMode = localStorage.getItem('viewMode') || 'active';
          await this.syncShowMode(currentViewMode);

          await this.loadElements();
        } else {
          this.headline = '';
          this.lockedElementId = null;
          // For non-authenticated users, use localStorage
          const storedLang = localStorage.getItem('lang');
          if (storedLang) {
            this.lang = storedLang;
          }
          const storedViewMode = localStorage.getItem('viewMode');
          if (storedViewMode) {
            this.viewMode = storedViewMode;
          }
        }
      } catch (error) {
        console.error('Error checking auth:', error);
        this.user = null;
        this.headline = '';
        this.lockedElementId = null;
        // For non-authenticated users, use localStorage
        const storedLang = localStorage.getItem('lang');
        if (storedLang) {
          this.lang = storedLang;
        }
        const storedViewMode = localStorage.getItem('viewMode');
        if (storedViewMode) {
          this.viewMode = storedViewMode;
        }
      } finally {
        this.loading = false;
      }
    },
    toggleLock(elementId) {
      // Persist lock server-side (only one locked element per user)
      const previous = this.lockedElementId;
      const next = this.lockedElementId === elementId ? null : elementId;

      // Optimistic UI update
      this.lockedElementId = next;

      axios.put('/api/user/locked-element', { locked_element_id: next })
        .then((res) => {
          if (res.data?.user) {
            this.user = res.data.user;
            this.lockedElementId = this.user.locked_element_id ?? null;
          }
        })
        .catch((err) => {
          console.error('Error updating locked element:', err);
          // revert
          this.lockedElementId = previous;
        });
    },
    async handleRegister() {
      try {
        // Get current locale from localStorage (or default to 'en')
        const locale = localStorage.getItem('lang') || 'en';
        const response = await axios.post('/api/register', {
          ...this.registerForm,
          locale: locale
        });
        this.user = response.data.user;
        // Initialize headline from headline (or empty string if null)
        // Width limitation is handled dynamically by checkHeadlineWidth() method
        const rawHeadline = this.user.headline !== null && this.user.headline !== undefined ? this.user.headline : '';
        this.headline = rawHeadline;

        // Sync locale and show_mode
        if (this.user.locale) {
          this.lang = this.user.locale;
          localStorage.setItem('lang', this.user.locale);
        } else {
          this.lang = locale;
          localStorage.setItem('lang', locale);
        }

        const currentViewMode = localStorage.getItem('viewMode') || 'active';
        await this.syncShowMode(currentViewMode);

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
        // Initialize headline from headline (or empty string if null)
        // Width limitation is handled dynamically by checkHeadlineWidth() method
        const rawHeadline = this.user.headline !== null && this.user.headline !== undefined ? this.user.headline : '';
        this.headline = rawHeadline;

        // Sync locale and show_mode
        const currentLang = localStorage.getItem('lang') || 'en';
        await this.syncLocale(currentLang);

        const currentViewMode = localStorage.getItem('viewMode') || 'active';
        await this.syncShowMode(currentViewMode);

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
        console.error('Error response:', error.response);
        console.error('Error response data:', error.response?.data);
        if (error.response && error.response.data) {
          if (error.response.data.errors) {
            const errors = error.response.data.errors;
            const errorMessages = Object.values(errors).flat().join(', ');
            alert(errorMessages);
          } else if (error.response.data.message) {
            alert(error.response.data.message);
          } else {
            alert(this.t('failedLogin') + ': ' + JSON.stringify(error.response.data));
          }
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

        // Reset to login page
        this.user = null;
          this.headline = '';
        this.elements = [];
        this.showRegisterForm = false; // Always show login form after logout
      } catch (error) {
        this.handleError(error, 'failedLogout', false);
      }
    },
    async loadElements() {
      try {
        this.loading = true;
        // Always load all elements - filtering is done by computed property
        const response = await axios.get('/api/elements');
        this.elements = response.data;

        // Load collapsed state from elements
        const collapsedState = {};
        this.elements.forEach(element => {
          if (element.collapsed === true) {
            collapsedState[element.id] = true;
          }
        });
        this.collapsedElements = collapsedState;
      } catch (error) {
        this.handleError(error, 'failedLoad', false);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Update order of elements in a specific group (by parent_element_id)
     * @param {number|null} parentId - The parent_element_id of the group (null for root elements)
     * @returns {Promise<boolean>} - Returns true if successful, false otherwise
     */
    async updateElementOrderInGroup(parentId) {
      const groupElements = this.elements.filter(e => e.parent_element_id === parentId);

      if (groupElements.length === 0) {
        return true; // No elements to update
      }

      // Create updates array with new order values based on current array order
      const updates = groupElements.map((element, index) => ({
        id: element.id,
        order: index + 1
      }));

      try {
        const response = await axios.put('/api/elements/reorder', {
          updates: updates,
          parent_element_id: parentId
        });

        // Update local state with server response (in case server made adjustments)
        if (response.data.elements) {
          response.data.elements.forEach(serverElement => {
            const localIndex = this.elements.findIndex(e => e.id === serverElement.id);
            if (localIndex !== -1) {
              this.elements[localIndex].order = serverElement.order;
            }
          });
        }
        return true;
      } catch (error) {
        console.error('Error reordering elements:', error);
        return false;
      }
    },

    /**
     * Update order in both old and new parent groups after element parent change
     * @param {number|null} oldParentId - The old parent_element_id
     * @param {number|null} newParentId - The new parent_element_id
     * @returns {Promise<boolean>} - Returns true if successful, false otherwise
     */
    async updateElementOrderAfterParentChange(oldParentId, newParentId) {
      // Update order in new parent group
      const newGroupSuccess = await this.updateElementOrderInGroup(newParentId);
      if (!newGroupSuccess) {
        return false;
      }

      // Update order in old parent group (if element was moved from another group)
      if (oldParentId !== newParentId && oldParentId !== null) {
        const oldGroupSuccess = await this.updateElementOrderInGroup(oldParentId);
        if (!oldGroupSuccess) {
          return false;
        }
      }

      return true;
    },

    closeAddModal() {
      this.showAddModal = false;
    },

    async handleAddElement(newElementData) {
      try {
        const response = await axios.post('/api/elements', newElementData);
        const newElement = response.data;

        // Add to elements array (filtering is handled by computed property)
        this.elements.unshift(newElement);

        this.closeAddModal();
      } catch (error) {
        this.handleError(error, 'failedAdd', false);
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
        this.handleError(error, 'failedUpdate', false);
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
        this.handleError(error, 'failedUpdate', false);
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

        // Force-unlock if the locked element is the one being archived or is within its subtree
        if (this.lockedElementId !== null) {
          const lockedId = this.lockedElementId;
          let cur = this.elements.find(e => e.id === lockedId);
          let shouldUnlock = lockedId === id;
          while (!shouldUnlock && cur && cur.parent_element_id) {
            if (cur.parent_element_id === id) {
              shouldUnlock = true;
              break;
            }
            cur = this.elements.find(e => e.id === cur.parent_element_id);
          }

          if (shouldUnlock) {
            const previous = this.lockedElementId;
            this.lockedElementId = null;
            axios.put('/api/user/locked-element', { locked_element_id: null })
              .then((res) => {
                if (res.data?.user) {
                  this.user = res.data.user;
                  this.lockedElementId = this.user.locked_element_id ?? null;
                }
              })
              .catch((err) => {
                console.error('Error clearing locked element after archive:', err);
                // revert if backend failed
                this.lockedElementId = previous;
              });
          }
        }
      } catch (error) {
        this.handleError(error, 'failedArchive', false);
      }
    },

    async restoreElement(id) {
      try {
        await axios.post(`/api/elements/${id}/restore`);

        // Update local state for element and all its descendants
        this.updateElementAndDescendants(id, { archived: false });
        // Note: Filtering is handled automatically by filteredElements computed property
      } catch (error) {
        this.handleError(error, 'failedRestore', false);
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
        this.handleError(error, 'failedRemove', false);
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
    /**
     * Check if element has children
     * @param {number} elementId - The element ID
     * @returns {boolean} - True if element has children
     */
    hasChildren(elementId) {
      return this.filteredElements.some(e => e.parent_element_id === elementId);
    },
    /**
     * Toggle collapse state for an element
     * @param {number} elementId - The element ID to toggle
     */
    async toggleCollapse(elementId) {
      // Get current collapsed state
      const newCollapsedState = !this.collapsedElements[elementId];

      // Update local state immediately for responsive UI
      this.collapsedElements = {
        ...this.collapsedElements,
        [elementId]: newCollapsedState
      };

      // Save to database
      try {
        await axios.put(`/api/elements/${elementId}/toggle-collapse`, {
          collapsed: newCollapsedState
        });

        // Update element in local array
        const element = this.elements.find(e => e.id === elementId);
        if (element) {
          element.collapsed = newCollapsedState;
        }
      } catch (error) {
        // Revert on error
        this.collapsedElements = {
          ...this.collapsedElements,
          [elementId]: !newCollapsedState
        };
        console.error('Error saving collapse state:', error);
        await this.handleError(error, 'failedUpdate', false);
      }
    },
    /**
     * Preserve element width before it becomes absolutely positioned
     * This prevents elements from shrinking when position: absolute is applied
     * @param {HTMLElement} el - The element that is about to leave
     */
    onBeforeLeave(el) {
      // Save the current width before position: absolute is applied
      const width = el.offsetWidth;
      if (width > 0) {
        el.style.width = width + 'px';
      }
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
        this.handleError(error, 'failedUpdate', false);
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
    /**
     * Check if element should be moved (not dropping at original position)
     * @param {number} actualDropIndex - The drop index
     * @returns {boolean} - True if element should be moved
     */
    shouldMoveElement(actualDropIndex) {
      // If dropping back at the original position (between the same neighbors), don't move
      if (actualDropIndex === this.draggingIndex) {
        return false;
      }
      // If dropping at draggingIndex + 1 and it's not the bottom position, it's the original position
      if (actualDropIndex === this.draggingIndex + 1 && actualDropIndex !== this.hierarchicalElements.length) {
        return false;
      }
      return true;
    },
    /**
     * Calculate insertion index in elements array based on drop position in hierarchicalElements
     * @param {Object} originalElement - The element to move
     * @param {number} actualDropIndex - The target drop index in hierarchicalElements
     * @returns {number} - The new insertion index in elements array (before removal)
     */
    calculateInsertIndex(originalElement, actualDropIndex) {
      if (actualDropIndex === this.hierarchicalElements.length) {
        // Moving to the bottom - insert at the end
        return this.elements.length;
      } else {
        // Moving between elements
        // Find the target element in hierarchicalElements and then find it in original elements
        const targetElement = this.hierarchicalElements[actualDropIndex];
        if (targetElement) {
          const targetOriginalIndex = this.elements.findIndex(e => e.id === targetElement.id);
          if (targetOriginalIndex !== -1) {
            return targetOriginalIndex;
          }
        }
      }
      return this.elements.length; // Default to end
    },
    /**
     * Move element in the elements array
     * @param {Object} originalElement - The element to move
     * @param {number} insertIndex - The index to insert at (before removal)
     */
    moveElementInArray(originalElement, insertIndex) {
      const newElements = [...this.elements];
      const originalIndex = this.elements.findIndex(e => e.id === originalElement.id);

      // Remove the dragged element from its original position
      newElements.splice(originalIndex, 1);

      // Adjust insertIndex if element was removed before target position
      const adjustedInsertIndex = originalIndex < insertIndex ? insertIndex - 1 : insertIndex;

      // Insert it at the new position
      newElements.splice(adjustedInsertIndex, 0, originalElement);

      // Update local state first for immediate UI feedback
      this.elements = newElements;
    },
    /**
     * Calculate target order in the new parent group based on drop position
     * @param {Object} originalElement - The element being moved
     * @param {number|null} newParentId - The new parent ID
     * @param {number} actualDropIndex - The drop index in hierarchicalElements
     * @returns {number} - The target order in the new group
     */
    calculateTargetOrder(originalElement, newParentId, actualDropIndex) {
      // Get all elements that will be in the new parent group (excluding the moved element)
      const newGroupElements = this.elements.filter(e =>
        e.id !== originalElement.id && e.parent_element_id === newParentId
      );

      // Find the target element in hierarchicalElements
      const targetElement = actualDropIndex < this.hierarchicalElements.length
        ? this.hierarchicalElements[actualDropIndex]
        : null;

      if (!targetElement) {
        // Dropping at the end
        return newGroupElements.length + 1;
      }

      // Find the target element in the new group
      const targetInGroup = newGroupElements.find(e => e.id === targetElement.id);
      if (!targetInGroup) {
        // Target element is not in the new group (shouldn't happen, but fallback)
        return newGroupElements.length + 1;
      }

      // Find the index of target element in the new group
      const targetIndexInGroup = newGroupElements.findIndex(e => e.id === targetElement.id);

      // The target order is the position in the group (1-based)
      return targetIndexInGroup + 1;
    },
    /**
     * Move element atomically using the new move API endpoint
     * This ensures parent change and reordering happen in one transaction
     * and DOM updates happen in a single action
     * @param {Object} originalElement - The element to move
     * @param {number|null} newParentId - The new parent ID
     * @param {number} actualDropIndex - The drop index in hierarchicalElements
     */
    async moveElementAtomically(originalElement, newParentId, actualDropIndex) {
      try {
        // Calculate target order in the new parent group
        const targetOrder = this.calculateTargetOrder(originalElement, newParentId, actualDropIndex);

        // Call the move API endpoint
        const response = await axios.put('/api/elements/move', {
          element_id: originalElement.id,
          new_parent_id: newParentId,
          target_order: targetOrder
        });

        // Update local state with all affected elements from server response
        // This ensures DOM updates happen in a single action
        if (response.data.elements && Array.isArray(response.data.elements)) {
          // Create a map of updated elements for quick lookup
          const updatedElementsMap = new Map(
            response.data.elements.map(e => [e.id, e])
          );

          // Update affected elements in place
          for (let i = 0; i < this.elements.length; i++) {
            const element = this.elements[i];
            if (updatedElementsMap.has(element.id)) {
              // Update existing element with server data
              Object.assign(this.elements[i], updatedElementsMap.get(element.id));
            }
          }

          // Re-sort elements array to match the new order
          // This ensures the DOM updates in a single action with correct order
          this.elements.sort((a, b) => {
            // First by parent_element_id (null first)
            if (a.parent_element_id !== b.parent_element_id) {
              if (a.parent_element_id === null) return -1;
              if (b.parent_element_id === null) return 1;
              return a.parent_element_id - b.parent_element_id;
            }
            // Then by order
            if (a.order !== b.order) {
              return (a.order || 0) - (b.order || 0);
            }
            // Finally by created_at
            return new Date(a.created_at) - new Date(b.created_at);
          });
        } else {
          // Fallback: reload all elements if response format is unexpected
          await this.loadElements();
        }
      } catch (error) {
        await this.handleError(error, 'failedUpdate', true);
        throw error;
      }
    },
    /**
     * Update parent and order after element move
     * @param {Object} originalElement - The moved element
     * @param {number|null} newParentId - The new parent ID
     */
    async updateParentAndOrder(originalElement, newParentId) {
      const parentChanged = originalElement.parent_element_id !== newParentId;

      if (parentChanged) {
        try {
          await axios.put(`/api/elements/${originalElement.id}`, {
            parent_element_id: newParentId
          });

          // Update local state
          const elementIndex = this.elements.findIndex(e => e.id === originalElement.id);
          if (elementIndex !== -1) {
            const oldParentId = originalElement.parent_element_id;
            this.elements[elementIndex].parent_element_id = newParentId;

            // Update order in both old and new parent groups
            // This must be called AFTER moving the element in the array
            const success = await this.updateElementOrderAfterParentChange(oldParentId, newParentId);
            if (!success) {
              throw new Error('Failed to update element order');
            }
          }
        } catch (error) {
          await this.handleError(error, 'failedUpdate', true);
          throw error; // Re-throw to allow caller to handle
        }
      } else {
        // Parent didn't change, just update order in the same group
        const parentId = originalElement.parent_element_id;
        const success = await this.updateElementOrderInGroup(parentId);

        if (!success) {
          // Revert to original order on error
          await this.handleError(new Error('Failed to update order'), 'failedUpdate', true);
          throw new Error('Failed to update order');
        }
      }
    },
    /**
     * Determine drop position from mouse coordinates when dragOverIndex is null
     * @param {Event} event - The drop event
     * @param {HTMLElement} listContainer - The list container element
     */
    determineDropPositionFromMouse(event, listContainer) {
      const allElements = listContainer.querySelectorAll('[draggable="true"]');
      if (allElements.length === 0) {
        return;
      }

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
          return;
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
    },
    /**
     * Handle dropping element on middle third (making it a child)
     * @param {Object} draggedElement - The element being dragged
     * @param {Object} parentElement - The element to become parent
     */
    async handleMiddleDrop(draggedElement, parentElement) {
      // Prevent element from being its own parent or child
      if (draggedElement.id === parentElement.id) {
        this.dragOverIndex = null;
        return;
      }

      try {
        await axios.put(`/api/elements/${draggedElement.id}`, {
          parent_element_id: parentElement.id
        });

        // Update local state
        const elementIndex = this.elements.findIndex(e => e.id === draggedElement.id);
        if (elementIndex !== -1) {
          const oldParentId = this.elements[elementIndex].parent_element_id;
          this.elements[elementIndex].parent_element_id = parentElement.id;

          // Update order in both old and new parent groups
          const success = await this.updateElementOrderAfterParentChange(oldParentId, parentElement.id);
          if (!success) {
            throw new Error('Failed to update element order');
          }
        }
      } catch (error) {
        await this.handleError(error, 'failedUpdate', true);
        this.dragOverIndex = null;
        return;
      }
      this.dragOverIndex = null;
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
            classes.push('mb-[1.25rem]'); // 20px = 1.25rem
          } else if (index === this.hoverElementIndex + 1) {
            // Lower element - increase top margin
            classes.push('mt-[1.25rem]'); // 20px = 1.25rem
          }
        } else if (this.hoverElementPart === 'above') {
          // Only first element in drop zone - always increase top margin
          // (mouse is above the element, even if outside the list)
          if (index === 0) {
            classes.push('mt-[2.55rem]'); // 40.8px = 2.55rem // Special case for first root element
          }
        } else if (this.hoverElementPart === 'below') {
          // Only last element in drop zone - always increase bottom margin
          // (mouse is below the element, even if outside the list)
          if (index === this.hierarchicalElements.length - 1) {
            classes.push('mb-[1.25rem]'); // 20px = 1.25rem // Always bottom margin when mouse is below
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
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/html', event.target);

      // Add global dragover and drop listeners to track mouse position even outside the list
      document.addEventListener('dragover', this.handleDocumentDragOver);
      document.addEventListener('drop', this.handleDocumentDrop);
    },

    /**
     * Check if mouse is above the first element
     * @param {number} mouseY - Mouse Y coordinate
     * @param {HTMLElement} firstElement - First element in the list
     * @returns {boolean} - True if mouse is above first element
     */
    checkMouseAboveFirstElement(mouseY, firstElement) {
      if (!firstElement) return false;
      const firstRect = firstElement.getBoundingClientRect();
      const firstThirdHeight = firstRect.height / 3;
      return mouseY < firstRect.top + firstThirdHeight;
    },
    /**
     * Check if mouse is below the last element
     * @param {number} mouseY - Mouse Y coordinate
     * @param {HTMLElement} lastElement - Last element in the list
     * @returns {boolean} - True if mouse is below last element
     */
    checkMouseBelowLastElement(mouseY, lastElement) {
      if (!lastElement) return false;
      const lastRect = lastElement.getBoundingClientRect();
      const lastThirdHeight = lastRect.height / 3;
      return mouseY > lastRect.bottom - lastThirdHeight;
    },
    /**
     * Check if mouse is between two elements
     * @param {number} mouseY - Mouse Y coordinate
     * @param {HTMLElement} currentElement - Upper element
     * @param {HTMLElement} nextElement - Lower element
     * @returns {boolean} - True if mouse is between elements
     */
    checkMouseBetweenElements(mouseY, currentElement, nextElement) {
      if (!currentElement || !nextElement) return false;
      const currentRect = currentElement.getBoundingClientRect();
      const nextRect = nextElement.getBoundingClientRect();
      const currentLowerThirdStart = currentRect.bottom - currentRect.height / 3;
      const nextUpperThirdEnd = nextRect.top + nextRect.height / 3;
      return mouseY >= currentLowerThirdStart && mouseY <= nextUpperThirdEnd;
    },
    /**
     * Check if mouse is in the middle third of an element
     * @param {number} mouseY - Mouse Y coordinate
     * @param {HTMLElement} element - Element to check
     * @returns {boolean} - True if mouse is in middle third
     */
    checkMouseInMiddleThird(mouseY, element) {
      if (!element) return false;
      const elementRect = element.getBoundingClientRect();
      const elementHeight = elementRect.height;
      const thirdHeight = elementHeight / 3;
      const y = mouseY - elementRect.top;
      return y >= thirdHeight && y <= (elementHeight - thirdHeight);
    },
    /**
     * Check if mouse position is in a between-zone (not middle third)
     * @param {number} mouseY - Mouse Y coordinate
     * @param {HTMLElement} element - Element to check
     * @param {HTMLElement} prevElement - Previous element (if exists)
     * @param {HTMLElement} nextElement - Next element (if exists)
     * @returns {boolean} - True if in between zone
     */
    isMouseInBetweenZone(mouseY, element, prevElement, nextElement) {
      if (!element) return false;
      const elementRect = element.getBoundingClientRect();
      const thirdHeight = elementRect.height / 3;

      if (prevElement) {
        const prevRect = prevElement.getBoundingClientRect();
        const prevLowerThirdStart = prevRect.bottom - prevRect.height / 3;
        if (mouseY >= prevLowerThirdStart && mouseY <= elementRect.top + thirdHeight) {
          return true;
        }
      }

      if (nextElement) {
        const nextRect = nextElement.getBoundingClientRect();
        const nextUpperThirdEnd = nextRect.top + nextRect.height / 3;
        if (mouseY >= elementRect.bottom - thirdHeight && mouseY <= nextUpperThirdEnd) {
          return true;
        }
      }

      return false;
    },
    handleDocumentDragOver(event) {
      if (this.draggingIndex === null) {
        return;
      }

      event.preventDefault(); // Allow drop
      event.dataTransfer.dropEffect = 'move'; // Set drop effect to allow drop

      // Clear any pending dragLeave timeout since we're still dragging
      if (this.dragOverTimeout) {
        clearTimeout(this.dragOverTimeout);
        this.dragOverTimeout = null;
      }

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
      if (this.checkMouseAboveFirstElement(mouseY, firstElement)) {
        this.hoverElementIndex = 0;
        this.hoverElementPart = 'above';
        this.dragOverIndex = 0;
        this.dropZoneElements = [0];
        return;
      }

      // Check if mouse is below the last element (below lower third)
      const lastElement = elementArray[elementArray.length - 1];
      if (this.checkMouseBelowLastElement(mouseY, lastElement)) {
        this.hoverElementIndex = this.hierarchicalElements.length - 1;
        this.hoverElementPart = 'below';
        this.dragOverIndex = this.hierarchicalElements.length;
        this.dropZoneElements = [this.hierarchicalElements.length - 1];
        return;
      }

      // Check if mouse is between any two elements
      for (let i = 0; i < elementArray.length - 1; i++) {
        const currentElement = elementArray[i];
        const nextElement = elementArray[i + 1];

        if (this.checkMouseBetweenElements(mouseY, currentElement, nextElement)) {
          // Find the actual index in hierarchicalElements array (not elements array!)
          const currentElementId = currentElement.getAttribute('data-element-id');
          const hierarchicalIndex = this.hierarchicalElements.findIndex(e => e.id.toString() === currentElementId);

          if (hierarchicalIndex !== -1) {
            // Only update if the dragOverIndex actually changed to prevent flickering
            const newDragOverIndex = hierarchicalIndex + 1;
            if (this.dragOverIndex !== newDragOverIndex || this.hoverElementPart !== 'between') {
              this.hoverElementIndex = hierarchicalIndex;
              this.hoverElementPart = 'between';
              this.dragOverIndex = newDragOverIndex;
              this.dropZoneElements = [hierarchicalIndex, hierarchicalIndex + 1];
            }
            return;
          }
        }
      }

      // Check if mouse is in the middle third of any element
      for (let i = 0; i < elementArray.length; i++) {
        const element = elementArray[i];
        if (this.checkMouseInMiddleThird(mouseY, element)) {
          // Find the actual index in hierarchicalElements array (not elements array!)
          const elementId = element.getAttribute('data-element-id');
          const hierarchicalIndex = this.hierarchicalElements.findIndex(e => e.id.toString() === elementId);

          if (hierarchicalIndex !== -1 && hierarchicalIndex !== this.draggingIndex) {
            // Check if we're not in a 'between' zone
            const prevElement = i > 0 ? elementArray[i - 1] : null;
            const nextElement = i < elementArray.length - 1 ? elementArray[i + 1] : null;

            if (!this.isMouseInBetweenZone(mouseY, element, prevElement, nextElement)) {
              this.hoverElementIndex = hierarchicalIndex;
              this.hoverElementPart = 'middle';
              this.dragOverIndex = null;
              this.dropZoneElements = [hierarchicalIndex];
              return;
            }
          }
        }
      }
    },

    handleDragOver(event, index) {
      event.preventDefault();
      event.stopPropagation();
      event.dataTransfer.dropEffect = 'move'; // Set drop effect to allow drop
      this.mouseY = event.clientY;

      // Clear any pending dragLeave timeout since we're still dragging over
      if (this.dragOverTimeout) {
        clearTimeout(this.dragOverTimeout);
        this.dragOverTimeout = null;
      }

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
        if (index === 0 && this.checkMouseAboveFirstElement(mouseY, event.currentTarget)) {
          this.hoverElementIndex = 0;
          this.hoverElementPart = 'above';
          this.dragOverIndex = 0;
          this.dropZoneElements = [0];
          return;
        }

        // Check if mouse is below lower third of last element - insert at end
        if (index === this.hierarchicalElements.length - 1 && this.checkMouseBelowLastElement(mouseY, event.currentTarget)) {
          this.hoverElementIndex = this.hierarchicalElements.length - 1;
          this.hoverElementPart = 'below';
          this.dragOverIndex = this.hierarchicalElements.length;
          this.dropZoneElements = [this.hierarchicalElements.length - 1];
          return;
        }

        // Check if mouse is in the zone between previous and current element
        if (index > 0 && currentElementIndex > 0) {
          const prevElement = elementArray[currentElementIndex - 1];
          if (this.checkMouseBetweenElements(mouseY, prevElement, event.currentTarget)) {
            // Only update if the dragOverIndex actually changed to prevent flickering
            if (this.dragOverIndex !== index || this.hoverElementPart !== 'between') {
              this.hoverElementIndex = index - 1;
              this.hoverElementPart = 'between';
              this.dragOverIndex = index;
              this.dropZoneElements = [index - 1, index];
            }
            return;
          }
        }

        // Check if mouse is in the zone between current and next element
        if (index < this.hierarchicalElements.length - 1 && currentElementIndex < elementArray.length - 1) {
          const nextElement = elementArray[currentElementIndex + 1];
          if (this.checkMouseBetweenElements(mouseY, event.currentTarget, nextElement)) {
            // Only update if the dragOverIndex actually changed to prevent flickering
            if (this.dragOverIndex !== index + 1 || this.hoverElementPart !== 'between') {
              this.hoverElementIndex = index;
              this.hoverElementPart = 'between';
              this.dragOverIndex = index + 1;
              this.dropZoneElements = [index, index + 1];
            }
            return;
          }
        }

        // If mouse is in middle third and not in between-elements zone, show visual effect but don't allow drop
        if (this.checkMouseInMiddleThird(mouseY, event.currentTarget)) {
          const prevElement = currentElementIndex > 0 ? elementArray[currentElementIndex - 1] : null;
          const nextElement = currentElementIndex < elementArray.length - 1 ? elementArray[currentElementIndex + 1] : null;

          if (!this.isMouseInBetweenZone(mouseY, event.currentTarget, prevElement, nextElement)) {
            this.hoverElementIndex = index;
            this.hoverElementPart = 'middle';
            this.dragOverIndex = null;
            this.dropZoneElements = [];
            return;
          }
        }

        // Default fallback: if in upper third, insert before; if in lower third, insert after
        const y = mouseY - elementRect.top;
        const isUpperThird = y < thirdHeight;
        if (isUpperThird) {
          this.hoverElementIndex = index;
          this.hoverElementPart = 'upper';
          this.dragOverIndex = index;
          this.dropZoneElements = [];
        } else {
          this.hoverElementIndex = index;
          this.hoverElementPart = 'lower';
          this.dragOverIndex = index + 1;
          this.dropZoneElements = [];
        }
      }
    },


    handleDragLeave(event) {
      // Don't clear state immediately - wait a bit to see if we're moving to a neighboring element
      // This prevents flickering when moving between elements
      if (this.dragOverTimeout) {
        clearTimeout(this.dragOverTimeout);
      }

      this.dragOverTimeout = setTimeout(() => {
        // Only clear if we're actually leaving the element area completely
        // Check if relatedTarget is null or outside the list container
        const listContainer = event.currentTarget.closest('.space-y-3');
        if (!listContainer || (event.relatedTarget && !listContainer.contains(event.relatedTarget))) {
          // We're leaving the list area - clear drop zone
          this.dropZoneElements = [];
          // Only clear dragOverIndex if we're not in a between zone
          if (this.hoverElementPart !== 'between') {
            this.dragOverIndex = null;
          }
        }
        // If relatedTarget is another element in the list, don't clear - let dragover handle it
      }, 100); // Small delay to allow dragover on neighboring element to fire first
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
          await this.handleMiddleDrop(draggedElement, parentElement);
          return;
        }
        // Otherwise, it's an invalid drop
        return;
      }

      // Use dragOverIndex instead of dropIndex for accurate positioning
      const actualDropIndex = this.dragOverIndex;

      // Check if element should be moved
      if (!this.shouldMoveElement(actualDropIndex)) {
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

      // Move element atomically using the new move API endpoint
      // This ensures parent change and reordering happen in one transaction
      // and DOM updates happen in a single action
      try {
        await this.moveElementAtomically(originalElement, newParentId, actualDropIndex);
      } catch (error) {
        this.dragOverIndex = null;
        return;
      }

      this.dragOverIndex = null;
    },

    handleDragEnd(event) {
      // Clear any pending timeouts
      if (this.dragOverTimeout) {
        clearTimeout(this.dragOverTimeout);
        this.dragOverTimeout = null;
      }

      // Remove global listeners
      document.removeEventListener('dragover', this.handleDocumentDragOver);
      document.removeEventListener('drop', this.handleDocumentDrop);

      this.draggingIndex = null;
      this.dragOverIndex = null;
      this.hoverElementIndex = null;
      this.hoverElementPart = null;
      this.dropZoneElements = [];
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
        this.determineDropPositionFromMouse(event, event.currentTarget);
      }

      // Check if we're dropping on middle third (making it a child)
      if (this.dragOverIndex === null && this.hoverElementPart === 'middle' && this.hoverElementIndex !== null) {
        const draggedElement = this.hierarchicalElements[this.draggingIndex];
        const parentElement = this.hierarchicalElements[this.hoverElementIndex];
        await this.handleMiddleDrop(draggedElement, parentElement);
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
        this.determineDropPositionFromMouse(event, listContainer);
      }

      // Use the same drop logic as regular handleDrop
      if (this.dragOverIndex !== null) {
        await this.handleDrop(event, null);
      }
    }
  },
  async mounted() {
    // Initialize isEditingHeadline if not already set
    if (this.isEditingHeadline === undefined) {
      this.isEditingHeadline = false;
    }
    await this.checkAuth();
    // Update max width on window resize
    window.addEventListener('resize', this.updateMaxHeadlineWidth);
    this.updateMaxHeadlineWidth();
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.updateMaxHeadlineWidth);
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
    },
    headline(newVal) {
      // Check width when headline changes
      if (this.isEditingHeadline) {
        this.$nextTick(() => {
          this.checkHeadlineWidth();
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

/* Fade transition for dropdown */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
  transform-origin: top left;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-0.625rem); /* 10px = 0.625rem */
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
.list-move {
  transition: transform 0.3s ease-in-out !important;
}
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease-in-out;
}

/* Smooth height transition for list container when elements collapse/expand */
/* Note: height: auto doesn't animate, so we rely on transition-group's built-in transitions */
.space-y-3 {
  /* The transition-group handles item transitions, we just ensure smooth spacing */
  transition: gap 0.3s ease-in-out;
}

/* Ensure all list items maintain their width during collapse animation */
/* Elements use margin-left and reduced width to keep right edge aligned */
.space-y-3 > * {
  box-sizing: border-box;
  /* Prevent flex items from expanding beyond their natural width */
  flex-shrink: 0;
  min-width: 0;
  /* Width is set via inline styles to account for margin-left */
  /* This ensures: margin-left + width = 100%, keeping right edge aligned */
  /* Ensure elements don't expand beyond container during transitions */
  align-self: flex-start;
  /* Prevent elements from expanding beyond container during animations */
  overflow-x: hidden;
  /* Margin-left is used for indentation, width is reduced to keep right edge aligned */
  position: relative;
}

/* Element item wrapper - margin-left and reduced width keep right edge aligned */
.element-item-wrapper {
  /* Margin-left is used for indentation, width is reduced to keep right edge aligned */
  box-sizing: border-box;
  /* Width is set via inline styles to account for margin-left */
  /* This ensures right edge stays aligned: margin-left + width = 100% */
  margin-right: 0;
  /* Ensure elements don't overflow container */
  max-width: 100%;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(-1.25rem); /* 20px = 1.25rem */
}

.list-leave-to {
  opacity: 0;
  transform: translateY(1.25rem); /* 20px = 1.25rem */
}

.list-leave-active {
  /* Use position: absolute to allow smooth movement of remaining elements */
  position: absolute;
  /* Preserve element's width before it becomes absolutely positioned */
  /* Width will be set by Vue transition-group based on element's computed width */
  transition: all 0.3s ease-in-out, opacity 0.3s ease-in-out, transform 0.3s ease-in-out, width 0s;
  /* Preserve element's original width and margins during collapse animation */
  box-sizing: border-box;
  /* Ensure flex items don't expand beyond their container */
  flex-shrink: 1;
  min-width: 0;
  /* Prevent absolutely positioned elements from affecting parent layout */
  left: 0;
  right: 0;
  /* Ensure element doesn't cause horizontal overflow */
  max-width: 100%;
}

/* Smooth height transition for list container when elements collapse/expand */
/* The container needs to smoothly resize as children are added/removed */
.list-container {
  /* Use max-height for smooth animation (height: auto doesn't animate) */
  transition: max-height 0.3s ease-in-out;
  max-height: 99999px; /* Large value to accommodate all elements */
  overflow: hidden; /* Prevent elements from expanding beyond container during animation */
  padding-top: 3px; /* Ensure top border of first element is visible */
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  /* Prevent width changes during animations */
  position: relative;
  /* Isolate layout to prevent parent shifting */
  contain: layout;
  /* Ensure container doesn't expand horizontally */
  min-width: 0;
  /* Note: overflow-x: clip removed - elements use calc(100% - margin-left) 
     which should prevent overflow, but overflow: hidden above provides protection */
}

/* White background container - minimum height equals viewport height minus top/bottom padding */
/* Parent container has py-8 (2rem top + 2rem bottom = 4rem total) */
.bg-white.rounded-lg.shadow-lg {
  min-height: calc(100vh - 4rem);
}

/* Ensure white background container maintains static width during animations */
.white-background-container {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: visible;
  /* Prevent width changes during any animations */
  position: relative;
  /* Isolate layout to prevent shifting during child animations */
  contain: layout style;
  /* Prevent horizontal scrolling and shifting */
  isolation: isolate;
}

/* Ensure element list container doesn't expand beyond white background */
.element-list-container {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: visible;
}

/* Prevent main content wrapper from shifting during animations */
.main-content-wrapper {
  /* Prevent horizontal shifting during child animations */
  contain: layout;
  /* Ensure stable positioning */
  position: relative;
}

</style>

