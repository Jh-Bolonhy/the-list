<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">{{ t('todoList') }}</h1>
        <LanguageSwitcher :lang="lang" :t="t" @update:lang="setLang" />
        
        <!-- Add Todo Form -->
        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
          <h2 class="text-xl font-semibold mb-4">{{ t('addNewTodo') }}</h2>
          <form @submit.prevent="addTodo" class="space-y-4">
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ t('title') }}</label>
              <input
                v-model="newTodo.title"
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
                v-model="newTodo.description"
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

        <!-- Todo List -->
        <div class="space-y-4">
          <h2 class="text-xl font-semibold mb-4">{{ t('yourTodos') }}</h2>
          
          <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-600">{{ t('loading') }}</p>
          </div>

          <div v-else-if="todos.length === 0" class="text-center py-8">
            <p class="text-gray-500">{{ t('noTodos') }}</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="todo in todos"
              :key="todo.id"
              class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <!-- Checkbox -->
              <input
                type="checkbox"
                :checked="todo.completed"
                @change="toggleTodo(todo)"
                class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-4"
              />
              
              <!-- Todo Content -->
              <div class="flex-1">
                <div v-if="editingTodo?.id === todo.id" class="space-y-2">
                  <input
                    v-model="editingTodo.title"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <textarea
                    v-model="editingTodo.description"
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
                      todo.completed ? 'line-through text-gray-500' : 'text-gray-800'
                    ]"
                  >
                    {{ todo.title }}
                  </h3>
                  <p 
                    v-if="todo.description"
                    :class="[
                      'text-gray-600 mt-1',
                      todo.completed ? 'line-through' : ''
                    ]"
                  >
                    {{ todo.description }}
                  </p>
                  <p class="text-xs text-gray-400 mt-2">
                    {{ t('created') }}: {{ formatDate(todo.created_at) }}
                  </p>
                </div>
              </div>
              
              <!-- Actions -->
              <div v-if="editingTodo?.id !== todo.id" class="flex space-x-2 ml-4">
                <button
                  @click="startEdit(todo)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm"
                >
                  {{ t('edit') }}
                </button>
                <button
                  @click="deleteTodo(todo.id)"
                  class="px-3 py-1 bg-orange-400 text-white rounded-md hover:bg-orange-500 text-sm"
                >
                  {{ t('delete') }}
                </button>
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
  name: 'TodoApp',
  components: { LanguageSwitcher },
  data() {
    return {
      todos: [],
      loading: true,
      newTodo: {
        title: '',
        description: ''
      },
      editingTodo: null,
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
    async loadTodos() {
      try {
        this.loading = true;
        const response = await axios.get('/api/elements');
        this.todos = response.data;
      } catch (error) {
        console.error('Error loading todos:', error);
        alert(this.t('failedLoad'));
      } finally {
        this.loading = false;
      }
    },
    
    async addTodo() {
      try {
        const response = await axios.post('/api/elements', this.newTodo);
        this.todos.unshift(response.data);
        this.newTodo = { title: '', description: '' };
      } catch (error) {
        console.error('Error adding todo:', error);
        alert(this.t('failedAdd'));
      }
    },
    
    async toggleTodo(todo) {
      try {
        const response = await axios.put(`/api/elements/${todo.id}`, {
          completed: !todo.completed
        });
        const index = this.todos.findIndex(t => t.id === todo.id);
        if (index !== -1) {
          this.todos[index] = response.data;
        }
      } catch (error) {
        console.error('Error toggling todo:', error);
        alert(this.t('failedUpdate'));
      }
    },
    
    startEdit(todo) {
      this.editingTodo = { ...todo };
    },
    
    async saveEdit() {
      try {
        const response = await axios.put(`/api/elements/${this.editingTodo.id}`, {
          title: this.editingTodo.title,
          description: this.editingTodo.description
        });
        const index = this.todos.findIndex(t => t.id === this.editingTodo.id);
        if (index !== -1) {
          this.todos[index] = response.data;
        }
        this.editingTodo = null;
      } catch (error) {
        console.error('Error updating todo:', error);
        alert(this.t('failedUpdate'));
      }
    },
    
    cancelEdit() {
      this.editingTodo = null;
    },
    
    async deleteTodo(id) {
      if (!confirm(this.t('confirmDelete'))) {
        return;
      }
      
      try {
        await axios.delete(`/api/elements/${id}`);
        this.todos = this.todos.filter(todo => todo.id !== id);
      } catch (error) {
        console.error('Error deleting todo:', error);
        alert(this.t('failedDelete'));
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
    await this.loadTodos();
  },
};
</script> 