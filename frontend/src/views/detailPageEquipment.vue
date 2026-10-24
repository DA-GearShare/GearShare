<script setup lang="ts">
    import {useRoute} from "vue-router";
    import {onMounted, ref} from "vue";
    import AccessoryCard from "@/components/AccessoryCard.vue";

    interface Accessory {
        accessoryEquipmentId: string
        name: string
        availableInRequestedPeriod: boolean
    }
    const route = useRoute();
    const accessories = ref<Accessory[]>([])

    onMounted( async ()=> {
        const res = await fetch(`/api/v1/equipment/${route.params.id}/accessories`)
        accessories.value = await res.json()
    })
</script>

<template>
    <h2>Empfehlungen</h2>

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
