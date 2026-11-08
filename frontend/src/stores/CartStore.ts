import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useCartStore = defineStore('cart', () => {
    const items = ref<string[]>([]);

    function has(id: string) {
        return items.value.includes(id);
    }

    function hasNot(id: string) {
        return !has(id);
    }

    function add(id: string) {
        if (!has(id)) {
            items.value.push(id);
        }
    }

    function remove(id: string) {
        items.value = items.value.filter(i => {
            return i !== id;
        });
    }

    return { items, has, hasNot, add, remove };
});
