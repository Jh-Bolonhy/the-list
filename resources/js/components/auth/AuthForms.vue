<template>
  <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
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
          <button type="button" @click="$emit('show-register')" class="text-blue-500 hover:underline">
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
          <button type="button" @click="$emit('show-login')" class="text-blue-500 hover:underline">
            {{ t('loginHere') }}
          </button>
        </p>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AuthForms',
  props: {
    showRegisterForm: {
      type: Boolean,
      default: false
    },
    loginForm: {
      type: Object,
      required: true
    },
    registerForm: {
      type: Object,
      required: true
    },
    t: {
      type: Function,
      required: true
    }
  },
  emits: ['login', 'register', 'show-register', 'show-login'],
  methods: {
    handleLogin() {
      this.$emit('login');
    },
    handleRegister() {
      this.$emit('register');
    }
  }
};
</script>

