<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useCompareStore } from '@/stores/CompareStore.ts';

const compare = useCompareStore();

const colors = [
    '#DFE9FB', '#E1EDDC', '#FFF0BE', '#E8E1F3', '#FDE2E4',
    '#D7F0F0', '#FCE7D6', '#E5E7EB', '#EADCF8', '#DDEFD3',
];

const focus = ref<Date[]>([new Date()]);
const calendarData = ref<any[]>([]);
const error = ref('');

const range = computed(() => {
    const d = focus.value[0] ?? new Date();
    return {
        from: new Date(d.getFullYear(), d.getMonth(), 1),
        to: new Date(d.getFullYear(), d.getMonth() + 1, 1),
    };
});

async function load() {
    error.value = '';

    if (compare.items.length === 0) {
        calendarData.value = [];
        return;
    }

    const res = await fetch('/api/v1/equipment/calendar', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            equipmentIds: compare.items,
            from: range.value.from.toISOString(),
            to: range.value.to.toISOString(),
        }),
    });

    if (!res.ok) {
        error.value = await res.text();
        return;
    }

    const data = await res.json();
    calendarData.value = data.equipment;
}

watch(
    () => [compare.items.join(','), range.value.from.getTime()],
    load,
    { immediate: true }
);

const events = computed(() => {
    return calendarData.value.flatMap((equipment: any, index: number) => {
        return equipment.busy.map((busy: any) => ({
            title: equipment.name,
            start: new Date(busy.start),
            end: new Date(busy.end),
            color: colors[index % colors.length],
        }));
    });
});
</script>

<template>
    <section class="compare">
        <div class="compare-header">
            <h2>Vergleichskalender</h2>
            <button type="button" class="clear-btn" @click="compare.clear()">Alle entfernen</button>
        </div>

        <p class="hint">
            {{ compare.items.length }} / {{ compare.MAX }} Geräte · Farbige Einträge zeigen, wann ein Gerät belegt ist.
        </p>

        <ul class="legend">
            <li v-for="(equipment, index) in calendarData" :key="equipment.id">
                <span class="dot" :style="{ background: colors[index % colors.length] }"></span>
                {{ equipment.name }}
                <button type="button" class="remove-btn" @click="compare.remove(equipment.id)">×</button>
            </li>
        </ul>

        <p v-if="error" class="error">{{ error }}</p>

        <v-calendar v-model="focus" view-mode="month" :events="events" />
    </section>
</template>

<style scoped>
.compare {
    padding: 16px;
    background: #FFFFFF;
    border: 1px solid #E8EAEE;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);
}

.compare-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.compare-header h2 {
    margin: 0;
    font-size: 18px;
    line-height: 26px;
    font-weight: 700;
    color: #12273C;
}

.hint {
    margin: 4px 0 12px;
    font-size: 13px;
    line-height: 18px;
    color: #6B7280;
}

.legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0 0 16px;
    padding: 0;
    list-style: none;
}

.legend li {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 4px 4px 10px;
    font-size: 13px;
    color: #374151;
    background: #F3F4F6;
    border-radius: 999px;
}

.dot {
    width: 12px;
    height: 12px;
    border: 1px solid rgba(17, 24, 39, 0.15);
    border-radius: 50%;
}

.remove-btn {
    width: 28px;
    height: 28px;
    font-size: 16px;
    background: transparent;
    border: none;
    border-radius: 999px;
    cursor: pointer;
}

.clear-btn {
    min-height: 40px;
    padding: 0 12px;
    font-size: 13px;
    font-weight: 700;
    color: #2456C8;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.error {
    margin: 0 0 12px;
    padding: 12px 16px;
    font-size: 14px;
    color: #B91C1C;
    background: #FEECEC;
    border-left: 3px solid #B91C1C;
    border-radius: 12px;
}
</style>
