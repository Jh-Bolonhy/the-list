import { ref } from 'vue';
import en from '../lang/en.js';
import ru from '../lang/ru.js';

const locales = { en, ru };

export function useI18n() {
  const lang = ref('en');

  const t = (key) => {
    return locales[lang.value][key] || key;
  };

  const setLang = (newLang) => {
    lang.value = newLang;
  };

  return {
    lang,
    t,
    setLang
  };
}

