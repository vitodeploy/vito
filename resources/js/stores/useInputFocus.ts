// stores/useInputFocus.ts
import { create } from 'zustand';

type InputFocusStore = {
  isFocused: boolean;
  setFocused: (value: boolean) => void;
};

export const useInputFocus = create<InputFocusStore>((set) => ({
  isFocused: false,
  setFocused: (value) => set({ isFocused: value }),
}));
