<script setup lang="ts">
    import {useRoute} from "vue-router";
    import {onMounted, ref} from "vue";
    import AccessorieCard from "@/components/AccessorieCard.vue";

    interface Accessory {
        accessoryEquipmentId: string
        name: string
        availableInRequestedPeriod: boolean
    }
    const route = useRoute();
    const accessories = ref<Accessory[]>([])
    const selected = ref<Record<string, boolean>>({})

    onMounted( async ()=> {
        const res = await fetch(`/api/v1/equipment/${route.params.id}/accessories`)
        accessories.value = await res.json()
        console.log("Halloooooo"+accessories.value)
    })
</script>

<template>
    <h2>Empfehlungen</h2>

    <AccessorieCard
        v-for="item in accessories"
        :key="item.accessoryEquipmentId"
        :name="item.name"
        :available="item.availableInRequestedPeriod"
        v-model="selected[item.accessoryEquipmentId]"
    />
</template>

<style scoped>

</style>
