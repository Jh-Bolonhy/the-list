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
      <div v-if="user" class="bg-white rounded-lg shadow-lg p-6 white-background-container flex flex-col">
        <!-- Header -->
        <div class="flex-shrink-0">
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
        </div>

        <!-- Element List -->
        <div ref="elementListContainer" class="space-y-4 element-list-container flex-1 overflow-y-auto styled-scrollbar">

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
            <template v-for="(element, index) in hierarchicalElements" :key="element.id">
              <!-- Group indicator for archived mode -->
              <div
                v-if="element.isGroupIndicator"
                @click="toggleGroupCollapse(element.groupPathKey)"
                class="p-3 cursor-pointer transition-all duration-200 group"
              >
                <div class="flex items-center flex-wrap gap-x-1 gap-y-1">
                  <template v-if="element.groupPath.length > 0">
                    <template v-for="(pathElement, idx) in element.groupPath" :key="pathElement.id">
                      <span
                        v-if="idx < element.groupPath.length - 1"
                        class="italic text-sm text-gray-600 group-hover:text-black transition-colors duration-[400ms]"
                      >
                        <span class="border-b border-transparent group-hover:border-black transition-all duration-[400ms]">{{ truncateTitle(pathElement.title) }}</span><span class="mx-1">></span>
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center italic text-sm text-gray-600 group-hover:text-black whitespace-nowrap transition-colors duration-[400ms]"
                      >
                        <span class="border-b border-transparent group-hover:border-black transition-all duration-[400ms]">{{ truncateTitle(pathElement.title) }}</span>
                        <button
                          class="ml-1 w-6 h-6 rounded-full flex items-center justify-center bg-gray-200 hover:bg-gray-100 text-gray-600 hover:text-gray-500 transition-all duration-[400ms] flex-shrink-0"
                          :class="{ 'rotate-90': !collapsedGroups[element.groupPathKey] }"
                          @click.stop="toggleGroupCollapse(element.groupPathKey)"
                          title="Toggle group"
                        >
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                        </button>
                      </span>
                    </template>
                  </template>
                  <span
                    v-else
                    class="inline-flex items-center italic text-sm text-gray-500 group-hover:text-black whitespace-nowrap transition-colors duration-[400ms]"
                  >
                    <span class="border-b border-transparent group-hover:border-black transition-all duration-[400ms]">{{ t('root') || 'Root' }}</span>
                    <button
                      class="ml-1 w-6 h-6 rounded-full flex items-center justify-center bg-gray-200 hover:bg-gray-100 text-gray-600 hover:text-gray-500 transition-all duration-[400ms] flex-shrink-0"
                      :class="{ 'rotate-90': !collapsedGroups[element.groupPathKey] }"
                      @click.stop="toggleGroupCollapse(element.groupPathKey)"
                      title="Toggle group"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </span>
                </div>
              </div>
              <!-- Regular element item -->
              <ElementItem
                v-else
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
                :all-elements="elements"
              />
            </template>
          </transition-group>
        </div>
      </div>
    </div>

    <!-- Add Element Modal -->
    <AddElementModal
      :show="showAddModal"
      :t="t"
      :locked-element-id="lockedElementId"
      :elements="elements"
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
      collapsedGroups: {}, // Object mapping group paths to collapse state (for archived mode)
      lockedElementId: null, // Only one locked parent per user; when set, show only it + descendants
      savedScrollPositionActive: null, // Saved scroll position for 'active' and 'both' modes
      savedScrollPositionArchived: null, // Saved scroll position for 'archived' mode
    };
  },
  computed: {
    // Filtered elements based on viewMode
    filteredElements() {
      if (this.viewMode === 'active') {
        return this.elements.filter(e => !Boolean(e.archived));
      } else if (this.viewMode === 'archived') {
        return this.elements.filter(e => Boolean(e.archived));
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
      const viewMode = this.viewMode;

      const filtered = this.filteredElements;
      const filteredById = new Map(filtered.map(e => [e.id, e]));
      const allById = new Map(this.elements.map(e => [e.id, e]));

      // Special handling for archived mode: group by path and lift to root
      if (viewMode === 'archived' && !lockedId) {
        // Build adjacency list for all archived elements (including those with active parents)
        const archivedElements = this.elements.filter(e => Boolean(e.archived));
        const archivedById = new Map(archivedElements.map(e => [e.id, e]));
        
        // Build children map for archived elements only
        const archivedChildrenByParent = new Map();
        for (const e of archivedElements) {
          const parentId = e.parent_element_id ?? null;
          if (!archivedChildrenByParent.has(parentId)) {
            archivedChildrenByParent.set(parentId, []);
          }
          archivedChildrenByParent.get(parentId).push(e);
        }

        // Find path from root to first active parent (or to root if all parents archived)
        const findPathToActiveParent = (elementId) => {
          // First, find the first active parent in the chain
          let currentId = elementId;
          const visited = new Set();
          let firstActiveParent = null;
          
          while (currentId !== null && currentId !== undefined) {
            if (visited.has(currentId)) break; // Prevent cycles
            visited.add(currentId);
            
            const element = allById.get(currentId);
            if (!element) break;
            
            // Check if parent exists and is active
            if (element.parent_element_id) {
              const parent = allById.get(element.parent_element_id);
              if (parent && !Boolean(parent.archived)) {
                firstActiveParent = parent;
                break;
              }
            }
            
            // Move to parent
            currentId = element.parent_element_id;
          }
          
          // If no active parent found, return empty path (root level)
          if (!firstActiveParent) {
            return [];
          }
          
          // Build path from root to first active parent (only active elements)
          const path = [];
          let pathId = firstActiveParent.id;
          const pathVisited = new Set();
          
          // Traverse from first active parent up to root
          while (pathId !== null && pathId !== undefined) {
            if (pathVisited.has(pathId)) break;
            pathVisited.add(pathId);
            
            const pathElement = allById.get(pathId);
            if (!pathElement) break;
            
            // Only add active (non-archived) elements to the path
            if (!Boolean(pathElement.archived)) {
              // Add to beginning to build path from root to first active parent
              path.unshift(pathElement);
            }
            
            pathId = pathElement.parent_element_id;
          }
          
          return path;
        };

        // Group archived elements by their path
        const groupsByPath = new Map();
        
        for (const element of archivedElements) {
          const path = findPathToActiveParent(element.id);
          // Create a unique key for the path
          const pathKey = path.map(p => p.id).join('|');
          
          if (!groupsByPath.has(pathKey)) {
            groupsByPath.set(pathKey, {
              path: path,
              pathKey: pathKey,
              elements: []
            });
          }
          groupsByPath.get(pathKey).elements.push(element);
        }

        // Sort groups by path depth and path element order
        const sortedGroups = Array.from(groupsByPath.values()).sort((a, b) => {
          // Shorter paths first
          if (a.path.length !== b.path.length) {
            return a.path.length - b.path.length;
          }
          // Then by first path element order
          if (a.path.length > 0 && b.path.length > 0) {
            const orderA = Number(a.path[0].order) || 0;
            const orderB = Number(b.path[0].order) || 0;
            if (orderA !== orderB) {
              return orderA - orderB;
            }
          }
          return 0;
        });

        // Process each group
        for (const group of sortedGroups) {
          const isGroupCollapsed = this.collapsedGroups[group.pathKey];
          
          // Add visual indicator for the group
          result.push({
            id: `group-${group.pathKey}`,
            isGroupIndicator: true,
            groupPath: group.path,
            groupPathKey: group.pathKey,
            level: 0
          });

          if (!isGroupCollapsed) {
            // Build a set of element IDs in this group for fast lookup
            const groupElementIds = new Set(group.elements.map(e => e.id));
            
            // Calculate relative levels within the group (preserve internal hierarchy)
            // Root elements in group have level 0, their children have level 1, etc.
            const calculateRelativeLevel = (elementId) => {
              const element = archivedById.get(elementId);
              if (!element) return 0;
              
              // If no parent, it's a root element in the group (level 0)
              if (!element.parent_element_id) {
                return 0;
              }
              
              // Check if parent is in the same group
              if (groupElementIds.has(element.parent_element_id)) {
                // Parent is in the group, so this is a child - calculate parent's level + 1
                return calculateRelativeLevel(element.parent_element_id) + 1;
              }
              
              // Parent is not in the group (it's active or in another group), so this is a root in this group
              return 0;
            };

            // Find root elements in the group (elements whose parent is not in the group)
            const rootElementsInGroup = group.elements.filter(element => {
              if (!element.parent_element_id) {
                return true; // No parent, it's a root
              }
              // Parent is not in the group
              return !groupElementIds.has(element.parent_element_id);
            });

            // Sort root elements by order
            rootElementsInGroup.sort((a, b) => {
              const orderA = Number(a.order) || 0;
              const orderB = Number(b.order) || 0;
              return orderA - orderB;
            });

            // Add elements from the group with their relative levels
            const addGroupSubtree = (element, relativeLevel) => {
              if (processed.has(element.id)) {
                return;
              }

              result.push({
                ...element,
                level: relativeLevel,
                groupPathKey: group.pathKey
              });
              processed.add(element.id);

              if (this.collapsedElements[element.id]) {
                return;
              }

              // Add children that are in the same group
              const children = (archivedChildrenByParent.get(element.id) || [])
                .filter(child => groupElementIds.has(child.id))
                .slice()
                .sort((a, b) => {
                  const orderA = Number(a.order) || 0;
                  const orderB = Number(b.order) || 0;
                  return orderA - orderB;
                });

              for (const child of children) {
                addGroupSubtree(child, relativeLevel + 1);
              }
            };

            // Process root elements in the group
            for (const rootElement of rootElementsInGroup) {
              addGroupSubtree(rootElement, 0);
            }
          }
        }

        return result;
      }

      // Original logic for 'active' and 'both' modes
      // Shared comparator for any "siblings list"
      // In 'both' mode: active first, then archived; always then by order.
      const compareWithinGroup = (a, b) => {
        const aArchived = Boolean(a.archived);
        const bArchived = Boolean(b.archived);
        if (viewMode === 'both' && aArchived !== bArchived) {
          return aArchived ? 1 : -1;
        }
        // Ensure order is a number for proper comparison
        const orderA = Number(a.order) || 0;
        const orderB = Number(b.order) || 0;
        return orderA - orderB;
      };

      // Build adjacency list for fast child lookup (filtered-only)
      const childrenByParent = new Map();
      for (const e of filtered) {
        const parentId = e.parent_element_id ?? null;
        if (!childrenByParent.has(parentId)) {
          childrenByParent.set(parentId, []);
        }
        childrenByParent.get(parentId).push(e);
      }

      // Memoized "real" level: traverses full parent chain (all elements),
      // so archived-only view can still indent correctly even when parents are active and filtered out.
      const levelCache = new Map();
      const visiting = new Set();
      const calculateLevel = (elementId) => {
        if (lockedId && elementId === lockedId) {
          return 0;
        }
        if (levelCache.has(elementId)) {
          return levelCache.get(elementId);
        }
        if (visiting.has(elementId)) {
          // Safety for corrupt cycles; treat as root
          return 0;
        }
        visiting.add(elementId);

        const element = allById.get(elementId);
        let level = 0;
        if (element && element.parent_element_id) {
          if (lockedId && element.parent_element_id === lockedId) {
            level = 1;
          } else {
            level = calculateLevel(element.parent_element_id) + 1;
          }
        }

        visiting.delete(elementId);
        levelCache.set(elementId, level);
        return level;
      };

      // Collapse propagation is limited to what is actually visible in the current filtered view.
      const isAnyParentCollapsed = (elementId) => {
        if (lockedId && elementId === lockedId) {
          return false;
        }
        let cur = filteredById.get(elementId);
        while (cur && cur.parent_element_id) {
          const parentId = cur.parent_element_id;
          // Stop collapse propagation above the locked root
          if (lockedId && parentId === lockedId) {
            return !!this.collapsedElements[lockedId];
          }
          if (this.collapsedElements[parentId]) {
            return true;
          }
          cur = filteredById.get(parentId);
        }
        return false;
      };

      const addSubtree = (element, level) => {
        if (processed.has(element.id)) {
          return;
        }
        if (isAnyParentCollapsed(element.id)) {
          return;
        }

        result.push({ ...element, level });
        processed.add(element.id);

        if (this.collapsedElements[element.id]) {
          return;
        }

        // Always sort children at traversal time (avoids any transient ordering glitches)
        const children = (childrenByParent.get(element.id) || [])
          .slice()
          .sort(compareWithinGroup);
        for (const child of children) {
          addSubtree(child, level + 1);
        }
      };

      // LOCK: only apply if the locked element exists in the current filtered view.
      // Levels in lock mode are relative to the locked element (start from 0).
      if (lockedId && filteredById.has(lockedId)) {
        const lockedElement = filteredById.get(lockedId);
        addSubtree(lockedElement, 0);
        return result;
      }

      // Root in filtered view: no parent OR parent is not present in the filtered set (virtual root)
      const isRootInFilteredView = (e) => !e.parent_element_id || !filteredById.has(e.parent_element_id);

      // Roots: sort by calculated level first (so virtual roots at deeper levels appear after shallow),
      // then by parent group, then within-group comparator.
      const roots = filtered
        .filter(isRootInFilteredView)
        .slice()
        .sort((a, b) => {
          const la = calculateLevel(a.id);
          const lb = calculateLevel(b.id);
          if (la !== lb) {
            return la - lb;
          }
          const pa = a.parent_element_id ?? 0;
          const pb = b.parent_element_id ?? 0;
          if (pa !== pb) {
            return pa - pb;
          }
          return compareWithinGroup(a, b);
        });

      for (const root of roots) {
        addSubtree(root, calculateLevel(root.id));
      }

      // Safety: add anything still unprocessed (corrupt/missing links), in a stable order
      const leftovers = filtered
        .filter(e => !processed.has(e.id))
        .slice()
        .sort((a, b) => {
          const la = calculateLevel(a.id);
          const lb = calculateLevel(b.id);
          if (la !== lb) {
            return la - lb;
          }
          const pa = a.parent_element_id ?? 0;
          const pb = b.parent_element_id ?? 0;
          if (pa !== pb) {
            return pa - pb;
          }
          return compareWithinGroup(a, b);
        });

      for (const e of leftovers) {
        addSubtree(e, calculateLevel(e.id));
      }

      return result;
    }
  },
  methods: {
    t(key) {
      return locales[this.lang][key] || key;
    },
    truncateTitle(title) {
      if (!title) return '';
      return title.length > 20 ? title.substring(0, 20) + '...' : title;
    },
    /**
     * Show notification to user
     * @param {string} messageKey - Translation key for message
     * @param {string} type - Notification type ('info', 'warning', 'error')
     */
    showNotification(messageKey, type = 'info') {
      const message = this.t(messageKey);
      if (type === 'warning') {
        console.warn(messageKey, message);
      } else if (type === 'error') {
        console.error(messageKey, message);
      } else {
        console.log(messageKey, message);
      }
    },
    /**
     * Check if an element is a descendant of another element
     * @param {number} ancestorId - The potential ancestor element ID
     * @param {number} descendantId - The potential descendant element ID
     * @returns {boolean} - True if descendantId is a descendant of ancestorId
     */
    isDescendant(ancestorId, descendantId) {
      const ancestor = Number(ancestorId);
      const target = Number(descendantId);

      if (!Number.isFinite(ancestor) || !Number.isFinite(target)) {
        return false;
      }

      if (ancestor === target) {
        return false; // Element is not its own descendant
      }
      
      // BFS to find all descendants
      const queue = [ancestor];
      const visited = new Set([ancestor]);
      
      while (queue.length > 0) {
        const currentId = queue.shift();
        const children = this.elements.filter(e => Number(e.parent_element_id) === currentId);
        
        for (const child of children) {
          const childId = Number(child.id);
          if (childId === target) {
            return true; // Found the descendant
          }
          if (Number.isFinite(childId) && !visited.has(childId)) {
            visited.add(childId);
            queue.push(childId);
          }
        }
      }
      
      return false; // Not a descendant
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
    /**
     * Get the ID of the topmost fully visible element in the scroll container
     * @returns {number|null} - Element ID or null if not found
     */
    getTopVisibleElementId() {
      const container = this.$refs.elementListContainer;
      if (!container) {
        return null;
      }

      const containerRect = container.getBoundingClientRect();
      const containerTop = containerRect.top;
      const containerBottom = containerRect.bottom;

      // Find the first element that is at least partially visible in the viewport
      // and is closest to the top of the visible area
      let topVisibleElementId = null;
      let minDistance = Infinity;

      for (let i = 0; i < this.hierarchicalElements.length; i++) {
        const element = this.hierarchicalElements[i];
        const elementEl = container.querySelector(`[data-element-id="${element.id}"]`);
        if (!elementEl) {
          continue;
        }

        const elementRect = elementEl.getBoundingClientRect();

        // Check if element is at least partially visible
        const isVisible = elementRect.bottom > containerTop && elementRect.top < containerBottom;

        if (isVisible) {
          // Calculate distance from element's top to container's visible top
          const distance = Math.abs(elementRect.top - containerTop);
          
          // If element's top is at or above container's top, it's a candidate
          if (elementRect.top <= containerTop && distance < minDistance) {
            minDistance = distance;
            topVisibleElementId = element.id;
          }
        }
      }

      // If we found a visible element, return it
      if (topVisibleElementId !== null) {
        return topVisibleElementId;
      }

      // If no visible element found, return the first element in the list
      if (this.hierarchicalElements.length > 0) {
        return this.hierarchicalElements[0].id;
      }

      return null;
    },
    async setViewMode(newViewMode) {
      // Save the ID of the topmost fully visible element before changing view mode
      const topElementId = this.getTopVisibleElementId();

      // Determine which position to save based on current mode
      const isCurrentModeArchived = this.viewMode === 'archived';
      const isNewModeArchived = newViewMode === 'archived';

      // Save current position before switching
      if (topElementId !== null) {
        if (isCurrentModeArchived) {
          // Save position for archived mode
          this.savedScrollPositionArchived = topElementId;
        } else {
          // Save position for active/both modes
          this.savedScrollPositionActive = topElementId;
        }
      }

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

      // Restore scroll position based on new mode
      let positionToRestore = null;
      if (isNewModeArchived) {
        positionToRestore = this.savedScrollPositionArchived;
      } else {
        positionToRestore = this.savedScrollPositionActive;
      }

      if (positionToRestore !== null) {
        this.$nextTick(() => {
          // Wait for hierarchicalElements to update
          this.$nextTick(() => {
            // Try to find the same element in the new view
            const elementExists = this.hierarchicalElements.some(e => e.id === positionToRestore);
            if (elementExists) {
              // Element exists in new view, scroll to it and align to top
              this.scrollToElement(positionToRestore, true);
            } else {
              // Element doesn't exist in new view, find the nearest element by ID
              // (elements with similar IDs are likely to be close in the hierarchy)
              const nearestElement = this.hierarchicalElements.find(e => e.id >= positionToRestore) ||
                                     this.hierarchicalElements[this.hierarchicalElements.length - 1];
              if (nearestElement) {
                this.scrollToElement(nearestElement.id, true);
              }
            }
          });
        });
      }
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
        // Normalize types from backend (booleans sometimes arrive as 0/1 during optimistic updates)
        this.elements = (response.data || []).map((e) => ({
          ...e,
          id: Number(e.id),
          parent_element_id: e.parent_element_id === null || e.parent_element_id === undefined ? null : Number(e.parent_element_id),
          order: e.order === null || e.order === undefined ? e.order : Number(e.order),
          archived: Boolean(e.archived),
          completed: Boolean(e.completed),
          collapsed: Boolean(e.collapsed),
        }));

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

    scrollToElement(elementId, alignToTop = false) {
      // Wait for DOM to update after element is added
      this.$nextTick(() => {
        const container = this.$refs.elementListContainer;
        if (!container) return;

        // Find the element in the DOM by data-element-id attribute
        const element = container.querySelector(`[data-element-id="${elementId}"]`);
        if (!element) return;

        const containerRect = container.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();

        // Check if element is outside visible area or needs repositioning
        const isAbove = elementRect.top < containerRect.top;
        const isBelow = elementRect.bottom > containerRect.bottom;
        const needsReposition = alignToTop && elementRect.top !== containerRect.top;

        if (isAbove || isBelow || needsReposition) {
          if (alignToTop) {
            // Scroll to align element's top with container's top
            const elementTop = element.offsetTop;
            container.scrollTo({
              top: Math.max(0, elementTop),
              behavior: 'smooth'
            });
          } else {
            // Calculate scroll position to center element in viewport
            const elementTop = element.offsetTop;
            const elementHeight = element.offsetHeight;
            const containerHeight = container.clientHeight;
            
            // Scroll to show element with some padding
            const scrollPosition = elementTop - (containerHeight / 2) + (elementHeight / 2);
            
            container.scrollTo({
              top: Math.max(0, scrollPosition),
              behavior: 'smooth'
            });
          }
        }
      });
    },

    handleKeyPress(event) {
      // Only handle keys when user is authenticated
      if (!this.user) return;

      // Handle ESC key to close modals
      if (event.key === 'Escape' || event.key === 'Esc') {
        // Check if input/textarea is focused - let default behavior work in form fields
        const activeElement = document.activeElement;
        const isInFormField = activeElement && (
          activeElement.tagName === 'INPUT' ||
          activeElement.tagName === 'TEXTAREA' ||
          activeElement.isContentEditable
        );

        // Priority: ConfirmModal > AddElementModal > Editing Element > Editing Headline
        if (this.showConfirmModal) {
          event.preventDefault();
          this.closeConfirmModal();
        } else if (this.showAddModal) {
          event.preventDefault();
          this.closeAddModal();
        } else if (this.editingElement) {
          event.preventDefault();
          this.cancelEdit();
        } else if (this.isEditingHeadline && !isInFormField) {
          event.preventDefault();
          this.cancelEditingHeadline();
        }
        return;
      }

      // Handle "+" key only when no modals are open
      if (this.showAddModal || this.showConfirmModal || this.editingElement) {
        return;
      }

      // Check if user is editing headline
      if (this.isEditingHeadline) {
        return;
      }

      // Check if input/textarea is focused (don't trigger when typing in form fields)
      const activeElement = document.activeElement;
      if (activeElement && (
        activeElement.tagName === 'INPUT' ||
        activeElement.tagName === 'TEXTAREA' ||
        activeElement.isContentEditable
      )) {
        return;
      }

      // Handle "+" key (both regular and numpad)
      if (event.key === '+' || event.key === '=' || (event.shiftKey && event.key === '=')) {
        event.preventDefault();
        this.showAddModal = true;
      }
    },

    async handleAddElement(newElementData) {
      try {
        // If locked element exists, set it as parent
        // Server will calculate the correct order automatically
        if (this.lockedElementId) {
          newElementData.parent_element_id = this.lockedElementId;
        }

        const response = await axios.post('/api/elements', newElementData);
        const rawNewElement = response.data;
        const newElement = {
          ...rawNewElement,
          id: Number(rawNewElement.id),
          parent_element_id: rawNewElement.parent_element_id === null || rawNewElement.parent_element_id === undefined
            ? null
            : Number(rawNewElement.parent_element_id),
          order: rawNewElement.order === null || rawNewElement.order === undefined ? rawNewElement.order : Number(rawNewElement.order),
          archived: Boolean(rawNewElement.archived),
          completed: Boolean(rawNewElement.completed),
          collapsed: Boolean(rawNewElement.collapsed),
        };

        // Ensure order is set from server response and is a number
        if (newElement.order === undefined || newElement.order === null) {
          // Fallback: calculate order locally if server didn't provide it
          const parentId = newElement.parent_element_id;
          const siblings = this.elements.filter(e => e.parent_element_id === parentId);
          newElement.order = siblings.length > 0 
            ? Math.max(...siblings.map(e => Number(e.order) || 0)) + 1
            : 1;
        } else {
          // Ensure order is a number, not a string
          newElement.order = Number(newElement.order);
        }

        // Add to local state (normalized)
        this.elements = [...this.elements, newElement];

        // IMPORTANT:
        // Immediately normalize local ordering to match server-side ordering.
        // This prevents the "new element appears at the top until reload" glitch.
        this.elements.sort((a, b) => {
          const pa = a.parent_element_id ?? null;
          const pb = b.parent_element_id ?? null;
          if (pa !== pb) {
            if (pa === null) return -1;
            if (pb === null) return 1;
            return Number(pa) - Number(pb);
          }
          const oa = Number(a.order) || 0;
          const ob = Number(b.order) || 0;
          if (oa !== ob) {
            return oa - ob;
          }
          return new Date(a.created_at) - new Date(b.created_at);
        });

        // Scroll to new element after it's rendered
        this.$nextTick(() => {
          this.scrollToElement(newElement.id);
        });

        this.closeAddModal();
      } catch (error) {
        this.handleError(error, 'failedAdd', false);
      }
    },

    async toggleElement(element) {
      // Optimistic update: update UI immediately
      const index = this.elements.findIndex(e => e.id === element.id);
      if (index === -1) return;
      
      const originalCompleted = element.completed;
      const newCompleted = !originalCompleted;
      
      // Update local state immediately
      this.elements[index].completed = newCompleted;
      
      try {
        const response = await axios.put(`/api/elements/${element.id}`, {
          completed: newCompleted
        });
        // Update with server response (in case server made adjustments)
        if (response.data) {
          // Normalize types from backend
          this.elements[index] = {
            ...response.data,
            id: Number(response.data.id),
            parent_element_id: response.data.parent_element_id === null || response.data.parent_element_id === undefined ? null : Number(response.data.parent_element_id),
            order: response.data.order === null || response.data.order === undefined ? response.data.order : Number(response.data.order),
            archived: Boolean(response.data.archived),
            completed: Boolean(response.data.completed),
            collapsed: Boolean(response.data.collapsed),
          };
        }
      } catch (error) {
        // Rollback on error
        this.elements[index].completed = originalCompleted;
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
          // Normalize types from backend
          this.elements[index] = {
            ...response.data,
            id: Number(response.data.id),
            parent_element_id: response.data.parent_element_id === null || response.data.parent_element_id === undefined ? null : Number(response.data.parent_element_id),
            order: response.data.order === null || response.data.order === undefined ? response.data.order : Number(response.data.order),
            archived: Boolean(response.data.archived),
            completed: Boolean(response.data.completed),
            collapsed: Boolean(response.data.collapsed),
          };
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
        const response = await axios.post(`/api/elements/${id}/restore`);

        // Update local state for restored parents (if any)
        if (response.data && response.data.restored_parents && Array.isArray(response.data.restored_parents)) {
          response.data.restored_parents.forEach(parentData => {
            const parentIndex = this.elements.findIndex(e => e.id === parentData.id);
            if (parentIndex !== -1) {
              // Update parent with full data from server
              Object.assign(this.elements[parentIndex], {
                ...parentData,
                id: Number(parentData.id),
                parent_element_id: parentData.parent_element_id === null || parentData.parent_element_id === undefined
                  ? null
                  : Number(parentData.parent_element_id),
                order: parentData.order === null || parentData.order === undefined ? parentData.order : Number(parentData.order),
                archived: Boolean(parentData.archived),
                completed: Boolean(parentData.completed),
                collapsed: Boolean(parentData.collapsed)
              });
            } else {
              // Parent not in local state, add it
              this.elements.push({
                ...parentData,
                id: Number(parentData.id),
                parent_element_id: parentData.parent_element_id === null || parentData.parent_element_id === undefined
                  ? null
                  : Number(parentData.parent_element_id),
                order: parentData.order === null || parentData.order === undefined ? parentData.order : Number(parentData.order),
                archived: Boolean(parentData.archived),
                completed: Boolean(parentData.completed),
                collapsed: Boolean(parentData.collapsed)
              });
            }
          });
        }

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
    toggleGroupCollapse(groupPathKey) {
      // Toggle group collapse state (local only, no database persistence needed)
      this.collapsedGroups = {
        ...this.collapsedGroups,
        [groupPathKey]: !this.collapsedGroups[groupPathKey]
      };
    },

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
          // Normalize types from backend (same as loadElements)
          const normalizedElements = response.data.elements.map((e) => ({
            ...e,
            id: Number(e.id),
            parent_element_id: e.parent_element_id === null || e.parent_element_id === undefined ? null : Number(e.parent_element_id),
            order: e.order === null || e.order === undefined ? e.order : Number(e.order),
            archived: Boolean(e.archived),
            completed: Boolean(e.completed),
            collapsed: Boolean(e.collapsed),
          }));

          // Create a map of updated elements for quick lookup
          const updatedElementsMap = new Map(
            normalizedElements.map(e => [e.id, e])
          );

          // Update affected elements in place
          for (let i = 0; i < this.elements.length; i++) {
            const element = this.elements[i];
            if (updatedElementsMap.has(element.id)) {
              // Update existing element with normalized server data
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
      // Prevent element from being its own parent
      if (draggedElement.id === parentElement.id) {
        this.dragOverIndex = null;
        return;
      }

      // Check for circular dependency: parent cannot be a descendant of dragged element
      if (this.isDescendant(draggedElement.id, parentElement.id)) {
        // Show user-friendly message
        this.showNotification('cannotNestParent', 'warning');
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
        // Check if error is about circular dependency
        if (error.response?.data?.error?.includes('descendant')) {
          this.showNotification('cannotNestParent', 'warning');
        } else {
          await this.handleError(error, 'failedUpdate', true);
        }
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

      // Check for circular dependency: new parent cannot be a descendant of dragged element
      if (newParentId !== null) {
        const newParent = this.elements.find(e => e.id === newParentId);
        if (newParent && this.isDescendant(originalElement.id, newParent.id)) {
          // Show user-friendly message
          this.showNotification('cannotNestParent', 'warning');
          this.dragOverIndex = null;
          return;
        }
      }

      // Move element atomically using the new move API endpoint
      // This ensures parent change and reordering happen in one transaction
      // and DOM updates happen in a single action
      try {
        await this.moveElementAtomically(originalElement, newParentId, actualDropIndex);
      } catch (error) {
        // Check if error is about circular dependency
        if (error.response?.data?.error?.includes('descendant')) {
          this.showNotification('cannotNestParent', 'warning');
        }
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
    // Add keyboard listener for "+" key
    window.addEventListener('keydown', this.handleKeyPress);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.updateMaxHeadlineWidth);
    window.removeEventListener('keydown', this.handleKeyPress);
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

/* White background container - fixed height for scrolling */
/* Parent container has py-8 (2rem top + 2rem bottom = 4rem total) */
.bg-white.rounded-lg.shadow-lg {
  height: calc(100vh - 4rem);
}

/* Ensure white background container maintains static width during animations */
.white-background-container {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: hidden; /* Container itself doesn't scroll */
  /* Prevent width changes during any animations */
  position: relative;
  /* Isolate layout to prevent shifting during child animations */
  contain: layout style;
  /* Prevent horizontal scrolling and shifting */
  isolation: isolate;
}

/* Element list container - scrollable area */
.element-list-container {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: auto; /* Enable vertical scrolling */
  /* Add padding for scrollbar if needed */
  padding-right: 0.5rem;
}

/* Prevent main content wrapper from shifting during animations */
.main-content-wrapper {
  /* Prevent horizontal shifting during child animations */
  contain: layout;
  /* Ensure stable positioning */
  position: relative;
}

</style>

