<script setup lang="ts">
    import {useRoute} from "vue-router";
    import {onMounted, ref, watch} from "vue";
    import AccessoryCard from "@/components/AccessoryCard.vue";

    interface Accessory {
        accessoryEquipmentId: string
        name: string
        availableInRequestedPeriod: boolean
    }
    const route = useRoute();
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
    />
</template>

<style scoped>

</style>
