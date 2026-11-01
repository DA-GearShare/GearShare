<script setup lang="ts">
    import {useRoute} from "vue-router";
    import {computed, onMounted, ref, watch} from "vue";
    import AccessoryCard from "@/components/AccessoryCard.vue";
    import {useAuthStore} from "@/stores/AuthStore.ts";
    import EquipmentAll from "@/components/EquipmentAll.vue";

    interface Accessory {
        accessoryEquipmentId: string
        name: string
        availableInRequestedPeriod: boolean
    }
    const route = useRoute();
    const autStore = useAuthStore();
    const accessories = ref<Accessory[]>([])

    const from = ref('')
    const to = ref('')

    async function loadAccessories() {
        let url = `/api/v1/equipment/${route.params.id}/accessories`

        if (from.value && to.value){
            url += `?from=${from.value}&to=${to.value}`
        }
        const res = await fetch(url)
        if (!res.ok) return
        accessories.value = await res.json()
    }

    watch([from, to], loadAccessories, {immediate: true})

    //Für Admins
    const isAdmin = computed(() => autStore.hasRole(['Admin']))
    const showPicker = ref(false)
    const error = ref('')

    const excludeIds = computed(() => [
        route.params.id as string,
        ...accessories.value.map(a => a.accessoryEquipmentId),
    ])

    async function addAccessory(accessoryId: string) {
        error.value = ''
        const res = await fetch(`/api/v1/equipment/${route.params.id}/accessories`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accessoryEquipmentId: accessoryId }),
        })
        if (!res.ok){
            error.value = await res.text()
            return
        }
        await loadAccessories()
    }
    async function removeAccessory(accessoryId: string) {
        error.value = ''
        const res = await fetch(`/api/v1/equipment/${route.params.id}/accessories/${accessoryId}`, {
            method: 'DELETE',
        })

        if (!res.ok) {
            error.value = await res.text()
            return
        }
        await loadAccessories()
    }
</script>

<template>
    <h2>Empfehlungen</h2>

    <label>
        Von
        <input type="datetime-local" v-model="from" />
    </label>
    <label>
        Bis
        <input type="datetime-local" v-model="to" />
    </label>

    <AccessoryCard
        v-for="item in accessories"
        :key="item.accessoryEquipmentId"
        :id="item.accessoryEquipmentId"
        :name="item.name"
        :available="item.availableInRequestedPeriod"
        :isAdmin="isAdmin"
        @remove="removeAccessory"
    />

    <button v-if="isAdmin" @click="showPicker = true">Accessory hinzufügen</button>
    <p v-if="error">{{ error }}</p>

    <div v-if="showPicker" class="overlay" @click.self="showPicker = false">
        <div class="popup">
            <button @click="showPicker = false">Schließen</button>
            <EquipmentAll
                picker-mode
                :exclude-ids="excludeIds"
                @select="addAccessory"
            />
        </div>
    </div>
</template>

<style scoped>
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.popup {
    background: white;
    width: 90%;
    max-width: 800px;
    max-height: 85vh;
    overflow-y: auto;
    padding: 20px;
    border-radius: 12px;
}
</style>
