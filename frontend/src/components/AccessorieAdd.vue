<script setup lang="ts">
import { ref, watch } from 'vue';
import SearchForm from '@/components/SearchForm.vue';
import ButtonsPagination from '@/components/ButtonsPagination.vue';

const props = defineProps<{
    // eslint-disable-next-line vue/prop-name-casing
    equipmentId: string
    // eslint-disable-next-line vue/prop-name-casing
    excludeIds: string[]
}>();

const emit = defineEmits(['select']);

const equipment = ref<any[]>([]);
const search = ref('');
const page = ref(1);
const totalPages = ref(0);

async function load() {
    const params = new URLSearchParams({ page: String(page.value), pageSize: '10' });
    if (search.value) {
        params.set('search', search.value);
    }

    const res = await fetch(`/api/v1/equipment?${params}`);
    if (!res.ok) return;

    const data = await res.json();
    equipment.value = data.items.filter((item: any) => {
        return item.id !== props.equipmentId;
    });
    totalPages.value = data.meta.totalPages;
}

function handleSearch(text: string) {
    search.value = text;
    page.value = 1;
}

function changePage(newPage: number) {
    page.value = newPage;
}

watch([search, page], load, { immediate: true });
</script>

<template>
    <SearchForm @search="handleSearch" />

    <ul class="picker-list">
        <li v-for="item in equipment" :key="item.id" class="picker-item">
            <div class="picker-info">
                <h3>{{ item.name }}</h3>
                <p>{{ item.model }} · {{ item.type }}</p>
            </div>

            <button
                type="button"
                class="btn-primary"
                :disabled="excludeIds.includes(item.id)"
                @click="emit('select', item.id)"
            >
                {{ excludeIds.includes(item.id) ? 'Bereits zugeordnet' : 'Hinzufügen' }}
            </button>
        </li>
    </ul>

    <ButtonsPagination v-if="totalPages" :page="page" :total_pages="totalPages" @changePage="changePage" />
</template>

<style scoped>
.picker-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 16px 0;
    padding: 0;
    list-style: none;
}

.picker-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #FFFFFF;
    border: 1px solid #E8EAEE;
    border-radius: 16px;
}

.picker-info {
    flex: 1;
    min-width: 0;
}

.picker-info h3 {
    margin: 0;
    font-size: 14px;
    line-height: 20px;
    font-weight: 700;
    color: #111827;
}

.picker-info p {
    margin: 0;
    font-size: 13px;
    line-height: 18px;
    color: #6B7280;
}
</style>
