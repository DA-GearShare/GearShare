<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useCompareStore } from '@/stores/CompareStore.ts';

const compare = useCompareStore();

const colors = ['#DFE9FB', '#E1EDDC', '#FFF0BE', '#E8E1F3', '#FDE2E4', '#D7F0F0', '#FCE7D6', '#E5E7EB', '#EADCF8', '#DDEFD3'];

const calendarData = ref<any[]>([]);
const error = ref('');

const weekOffset = ref(0);

function previousWeek() {
    weekOffset.value = weekOffset.value - 1;
}

function nextWeek() {
    weekOffset.value = weekOffset.value + 1;
}

const monday = computed(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    const day = d.getDay();
    d.setDate(d.getDate() - (day === 0 ? 6 : day - 1) + weekOffset.value * 7);
    return d;
});

const days = computed(() => {
    const result: Date[] = [];
    for (let i = 0; i <= 4; i++){
        const day = new Date(monday.value);
        day.setDate(day.getDate() + i);
        result[i] = day;
    }
    return result;
})
async function load() {
    error.value = '';

    if (compare.items.length === 0) {
        calendarData.value = [];
        return;
    }

    const saturday = new Date(monday.value);
    saturday.setDate(saturday.getDate() + 5);

    const res = await fetch('/api/v1/equipment/calendar', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            equipmentIds: compare.items,
            from: monday.value.toISOString(),
            to: saturday.toISOString(),
        }),
    });

    if (!res.ok) {
        error.value = await res.text();
        return;
    }

    calendarData.value = await res.json();
}

watch(() => [compare.items.join(','), weekOffset.value], load, { immediate: true });

function entriesFor(day: Date) {
    const dayEnd = new Date(day);
    dayEnd.setDate(dayEnd.getDate() + 1);

    const entries: any[] = [];

    calendarData.value.forEach((equipment: any, index: number) => {
        equipment.notAvailable.forEach((period: any) => {
            const start = new Date(period.from);
            const end = new Date(period.to);

            if (start < dayEnd && end > day) {
                entries.push({
                    name: equipment.name,
                    color: colors[index % colors.length],
                    from: start < day ? day : start,
                    to: end > dayEnd ? dayEnd : end,
                    dayEnd: dayEnd,
                });
            }
        });
    });

    return entries.sort((a, b) => a.from - b.from);
}

function formatTime(date: Date, dayEnd: Date) {
    if (date.getTime() === dayEnd.getTime()) return '24:00';
    return date.toLocaleTimeString('de-AT', { hour: '2-digit', minute: '2-digit' });
}

function formatDay(date: Date) {
    return date.toLocaleDateString('de-AT', { weekday: 'short', day: '2-digit', month: '2-digit' });
}

const weekTitle = computed(() => {
    const friday = days.value[4];
    return `${monday.value.toLocaleDateString('de-AT', { day: '2-digit', month: '2-digit' })} – ${friday.toLocaleDateString('de-AT')}`;
});
</script>

<template>
    <section class="compare">
        <div class="compare-header">
            <h2>Vergleichskalender</h2>
            <button type="button" class="clear-btn" @click="compare.clear()">Alle entfernen</button>
        </div>

        <ul class="legend">
            <li v-for="(equipment, index) in calendarData" :key="equipment.id">
                <span class="dot" :style="{ background: colors[index % colors.length] }"></span>
                {{ equipment.name }}
                <button type="button" class="remove-btn" @click="compare.remove(equipment.id)">×</button>
            </li>
        </ul>

        <p v-if="error" class="error">{{ error }}</p>

        <div class="week-nav">
            <button type="button" class="nav-btn" aria-label="Vorherige Woche" @click="previousWeek">‹</button>
            <span class="week-title">{{ weekTitle }}</span>
            <button type="button" class="nav-btn" aria-label="Nächste Woche" @click="nextWeek">›</button>
        </div>

        <div class="week">
            <div v-for="day in days" :key="day.getTime()" class="day">
                <p class="day-title">{{ formatDay(day) }}</p>

                <div
                    v-for="(entry, i) in entriesFor(day)"
                    :key="i"
                    class="entry"
                    :style="{ background: entry.color }"
                >
                    <strong>{{ entry.name }}</strong>
                    <span>{{ formatTime(entry.from, entry.dayEnd) }} – {{ formatTime(entry.to, entry.dayEnd) }}</span>
                </div>

                <p v-if="entriesFor(day).length === 0" class="free">frei</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.compare {
    padding: 16px;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #E8EAEE;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);
}

.compare-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.compare-header h2 {
    margin: 0;
    font-size: 18px;
    line-height: 26px;
    font-weight: 700;
    color: #12273C;
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
    background: transparent;
    border: none;
    border-radius: 999px;
    cursor: pointer;
}

.clear-btn {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 700;
    color: #2456C8;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.error {
    padding: 12px 16px;
    font-size: 14px;
    color: #B91C1C;
    background: #FEECEC;
    border-left: 3px solid #B91C1C;
    border-radius: 12px;
}

.week-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.nav-btn {
    width: 40px;
    height: 40px;
    font-size: 18px;
    font-weight: 700;
    color: #12273C;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    cursor: pointer;
}

.nav-btn:hover {
    background: #F3F4F6;
}

.week-title {
    font-size: 15px;
    font-weight: 700;
    color: #12273C;
}

.week {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    overflow: hidden;
}

.day {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-height: 160px;
    padding: 8px;
    border-left: 1px solid #E5E7EB;
}

.day:first-child {
    border-left: none;
}

.day-title {
    margin: -8px -8px 4px;
    padding: 8px;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    color: #374151;
    background: #F7F8FA;
    border-bottom: 1px solid #E5E7EB;
}

.entry {
    display: flex;
    flex-direction: column;
    padding: 6px 8px;
    font-size: 12px;
    line-height: 16px;
    color: #111827;
    border-radius: 10px;
}

.free {
    margin: 0;
    font-size: 12px;
    color: #6B7280;
    text-align: center;
}
</style>
