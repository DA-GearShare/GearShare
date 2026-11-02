<script setup lang="ts">
import { ref } from 'vue';
const emit = defineEmits(['filter']);

const show = ref(false);
const sort = ref('name');
const order = ref('asc');
const status = ref('');

const onChange = () => {
    emit('filter', sort.value, order.value, status.value);
};
</script>

<template>
    <div class="wrapper">
        <button class="btn-secondary filter-btn" type="button" :aria-expanded="show" @click="show = !show">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h10M18 7h2M4 17h2M10 17h10" />
                <circle cx="16" cy="7" r="2" />
                <circle cx="8" cy="17" r="2" />
            </svg>
            Filter
        </button>

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

            <h6>Status</h6>
            <ul>
                <li><label><input type="radio" value="available" v-model="status" @change="onChange"> Verfügbar</label></li>
                <li><label><input type="radio" value="reserved" v-model="status" @change="onChange"> Reserviert</label></li>
                <li><label><input type="radio" value="issued" v-model="status" @change="onChange"> Ausgeliehen</label></li>
                <li><label><input type="radio" value="" v-model="status" @change="onChange"> Statusfilterung entfernen</label></li>
            </ul>
        </div>
    </div>
</template>

<style scoped>
.wrapper {
    position: relative;
}

.filter-btn {
    gap: var(--space-s);
    min-height: 52px;
    border-radius: var(--radius-pill);
    border-color: var(--color-muted);
}

.menu {
    position: absolute;
    top: calc(100% + var(--space-s));
    right: 0;
    z-index: 10;
    min-width: 260px;
    padding: var(--space-l);
    background: var(--color-surface);
    border: var(--border-card);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
}

h6 {
    margin: var(--space-m) 0 var(--space-s);
    font-size: var(--text-label-size);
    line-height: var(--text-label-line);
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
}

h6:first-child {
    margin-top: 0;
}

ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

label {
    flex-direction: row;
    align-items: center;
    gap: var(--space-s);
    min-height: 32px;
    font-size: var(--text-body-size);
    line-height: var(--text-body-line);
    font-weight: var(--font-regular);
    color: var(--color-text-secondary);
    cursor: pointer;
}

input[type='radio'] {
    width: 18px;
    height: 18px;
    min-height: 0;
    padding: 0;
    accent-color: var(--color-navy);
}
</style>
