import { create } from 'zustand';

type ToastAction = { label: string; to: string };

type Toast = {
  id: number;
  message: string;
  action?: ToastAction | undefined;
};

type ToastState = {
  toasts: Toast[];
  show: (message: string, action?: ToastAction) => void;
  dismiss: (id: number) => void;
};

const VISIBLE_MS = 3000;
const MAX_VISIBLE = 3;

let nextId = 1;

export const useToastStore = create<ToastState>()((set, get) => ({
  toasts: [],
  show: (message, action) => {
    const id = nextId++;
    set(({ toasts }) => ({ toasts: [...toasts.slice(1 - MAX_VISIBLE), { id, message, action }] }));
    setTimeout(() => get().dismiss(id), VISIBLE_MS);
  },
  dismiss: (id) => set(({ toasts }) => ({ toasts: toasts.filter((toast) => toast.id !== id) })),
}));

export const toast = (message: string, action?: ToastAction) =>
  useToastStore.getState().show(message, action);
