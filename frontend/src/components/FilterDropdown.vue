<script setup lang="ts">
    import { ref } from 'vue';
    const emit = defineEmits(['filter']);

    const show = ref(false)
    const sort = ref('name');
    const order = ref('asc');

    const onChange = () => {
        emit('filter', sort.value, order.value)
    }
</script>

<template>
    <div class="wrapper">
        <button class="filter-btn" @click="show = !show">Filter</button>

        <div v-if="show" class="menu">
            <h6>Sortieren nach</h6>
            <ul>
                <li><label><input type="radio" value="name" v-model="sort" @change="onChange"> Name</label></li>
                <li><label><input type="radio" value="location" v-model="sort" @change="onChange"> Standort</label></li>
                <li><label><input type="radio" value="type" v-model="sort" @change="onChange"> Typ</label></li>
            </ul>

            <h6>Reihenfolge</h6>
            <ul>
                <li><label><input type="radio" value="asc" v-model="order" @change="onChange"> Aufsteigend</label></li>
                <li><label><input type="radio" value="desc" v-model="order" @change="onChange"> Absteigend</label></li>
            </ul>
        </div>
    </div>
</template>

<style scoped>

</style>
