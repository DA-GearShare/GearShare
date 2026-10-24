import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAccessoryStore = defineStore('accessory', () => {
    const selected = ref<string[]>([])

    function isSelected(id: string) {
        return selected.value.includes(id)
    }

    function toggle(id: string) {
        if (isSelected(id)) {
            selected.value = selected.value.filter(i => i !== id)
        } else {
            selected.value.push(id)
        }
    }

    return { selected, isSelected, toggle }
})
