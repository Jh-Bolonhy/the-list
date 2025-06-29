<template>
  <div class="mb-4 flex items-center space-x-2">
    <label class="font-medium">{{ t('language') }}:</label>
    <select v-model="current" @change="changeLang" class="border rounded px-2 py-1">
      <option value="en">{{ t('english') }}</option>
      <option value="ru">{{ t('russian') }}</option>
    </select>
  </div>
</template>

<script>
export default {
  name: 'LanguageSwitcher',
  props: {
    lang: String,
    t: Function
  },
  data() {
    return {
      current: this.lang || 'en',
    };
  },
  methods: {
    changeLang() {
      this.$emit('update:lang', this.current);
    }
  },
  watch: {
    lang(newVal) {
      this.current = newVal;
    }
  }
};
</script> 