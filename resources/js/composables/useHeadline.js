import { ref, nextTick } from 'vue';
import axios from 'axios';

export function useHeadline(user) {
  const headline = ref('');
  const isEditingHeadline = ref(false);
  const maxHeadlineWidth = ref(0);
  const pendingCursorPosition = ref(undefined);
  const initialHeadlineWidth = ref(null);
  const headlineInputRef = ref(null);
  const headerRowRef = ref(null);

  const measureTextWidth = (text, font) => {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    context.font = font;
    return context.measureText(text).width;
  };

  const updateMaxHeadlineWidth = () => {
    if (headerRowRef.value) {
      const rowWidth = headerRowRef.value.offsetWidth;
      maxHeadlineWidth.value = rowWidth / 3;
    }
  };

  const checkHeadlineWidth = () => {
    if (!headerRowRef.value || !headlineInputRef.value) {
      return;
    }
    
    const rowWidth = headerRowRef.value.offsetWidth;
    const maxWidth = rowWidth / 3;
    maxHeadlineWidth.value = maxWidth;
    
    const input = headlineInputRef.value;
    const computedStyle = window.getComputedStyle(input);
    const font = `${computedStyle.fontWeight} ${computedStyle.fontSize} ${computedStyle.fontFamily}`;
    
    const textWidth = measureTextWidth(headline.value, font);
    
    if (textWidth > maxWidth) {
      let trimmedText = headline.value;
      while (trimmedText.length > 0 && measureTextWidth(trimmedText, font) > maxWidth) {
        trimmedText = trimmedText.slice(0, -1);
      }
      headline.value = trimmedText;
      initialHeadlineWidth.value = maxWidth;
    } else {
      initialHeadlineWidth.value = textWidth;
    }
  };

  const getHeaderDisplay = (t, defaultHeader) => {
    if (!user.value) {
      return defaultHeader;
    }
    if (headline.value && headline.value.trim() !== '') {
      return headline.value;
    }
    return defaultHeader;
  };

  const startEditingHeadline = (event) => {
    if (!user.value) {
      return;
    }
    
    const clickX = event.clientX;
    const h1Element = event.currentTarget;
    const h1Rect = h1Element.getBoundingClientRect();
    const relativeX = clickX - h1Rect.left;
    const headlineText = headline.value || (user.value.headline || '');
    
    const h1Style = window.getComputedStyle(h1Element);
    const font = `${h1Style.fontWeight} ${h1Style.fontSize} ${h1Style.fontFamily}`;
    
    const textWidth = measureTextWidth(headlineText, font);
    initialHeadlineWidth.value = textWidth;
    
    let cursorPosition = headlineText.length;
    let currentWidth = 0;
    
    for (let i = 0; i < headlineText.length; i++) {
      const charWidth = measureTextWidth(headlineText[i], font);
      if (currentWidth + charWidth / 2 > relativeX) {
        cursorPosition = i;
        break;
      }
      currentWidth += charWidth;
      cursorPosition = i + 1;
    }
    
    pendingCursorPosition.value = cursorPosition;
    isEditingHeadline.value = true;
    
    nextTick(() => {
      const input = headlineInputRef.value;
      if (input) {
        updateMaxHeadlineWidth();
        input.focus();
        if (pendingCursorPosition.value !== undefined) {
          const pos = Math.min(pendingCursorPosition.value, headline.value.length);
          input.setSelectionRange(pos, pos);
          pendingCursorPosition.value = undefined;
        }
      }
    });
  };

  const finishEditingHeadline = async () => {
    if (!user.value) {
      return;
    }
    checkHeadlineWidth();
    await updateHeadline();
    isEditingHeadline.value = false;
    initialHeadlineWidth.value = null;
  };

  const cancelEditingHeadline = () => {
    if (!user.value) {
      return;
    }
    const rawHeadline = user.value.headline !== null && user.value.headline !== undefined ? user.value.headline : '';
    headline.value = rawHeadline;
    isEditingHeadline.value = false;
    initialHeadlineWidth.value = null;
  };

  const updateHeadline = async () => {
    if (!user.value) {
      return;
    }
    
    checkHeadlineWidth();
    const previousValue = user.value.headline !== null && user.value.headline !== undefined ? user.value.headline : '';
    
    try {
      const response = await axios.put('/api/user/headline', {
        headline: headline.value || null
      });
      
      if (response.data.user) {
        user.value.headline = response.data.user.headline;
        const rawHeadline = user.value.headline !== null && user.value.headline !== undefined ? user.value.headline : '';
        headline.value = rawHeadline;
      }
    } catch (error) {
      console.error('Error updating headline:', error);
      headline.value = previousValue;
    }
  };

  const initializeHeadline = () => {
    if (user.value) {
      const rawHeadline = user.value.headline !== null && user.value.headline !== undefined ? user.value.headline : '';
      headline.value = rawHeadline;
    } else {
      headline.value = '';
    }
  };

  return {
    headline,
    isEditingHeadline,
    maxHeadlineWidth,
    pendingCursorPosition,
    initialHeadlineWidth,
    headlineInputRef,
    headerRowRef,
    measureTextWidth,
    updateMaxHeadlineWidth,
    checkHeadlineWidth,
    getHeaderDisplay,
    startEditingHeadline,
    finishEditingHeadline,
    cancelEditingHeadline,
    updateHeadline,
    initializeHeadline
  };
}

