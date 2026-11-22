<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    equipmentId: string
}>();

const periods = ref<any[]>([]);
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
    for (let i = 0; i <= 4; i++) {
        const day = new Date(monday.value);
        day.setDate(day.getDate() + i);
        result[i] = day;
    }
    return result;
});

async function load() {
    const saturday = new Date(monday.value);
    saturday.setDate(saturday.getDate() + 5);

    const res = await fetch('/api/v1/equipment/calendar', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            equipmentIds: [props.equipmentId],
            from: monday.value.toISOString(),
            to: saturday.toISOString(),
        }),
    });

    if (!res.ok) return;

    const data = await res.json();
    periods.value = data[0]?.notAvailable ?? [];
}

watch(() => [props.equipmentId, weekOffset.value], load, { immediate: true });

function entriesFor(day: Date) {
    const dayEnd = new Date(day);
    dayEnd.setDate(dayEnd.getDate() + 1);

    const entries: any[] = [];

    periods.value.forEach((period: any) => {
        const start = new Date(period.from);
        const end = new Date(period.to);

        if (start < dayEnd && end > day) {
            entries.push({
                from: start < day ? day : start,
                to: end > dayEnd ? dayEnd : end,
                dayEnd: dayEnd,
            });
        }
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
    <section class="calendar">
        <h2>Verfügbarkeit</h2>

        <div class="week-nav">
            <button type="button" class="nav-btn" aria-label="Vorherige Woche" @click="previousWeek">‹</button>
            <span class="week-title">{{ weekTitle }}</span>
            <button type="button" class="nav-btn" aria-label="Nächste Woche" @click="nextWeek">›</button>
        </div>

        <div class="week">
            <div v-for="day in days" :key="day.getTime()" class="day">
                <p class="day-title">{{ formatDay(day) }}</p>

                <div v-for="(entry, i) in entriesFor(day)" :key="i" class="entry">
                    <strong>Reserviert</strong>
                    <span>{{ formatTime(entry.from, entry.dayEnd) }} – {{ formatTime(entry.to, entry.dayEnd) }}</span>
                </div>

                <p v-if="entriesFor(day).length === 0" class="free">frei</p>
            </div>
        </div>

        <slot />
    </section>
</template>

<style scoped>
.calendar {
    margin-top: 14px;
    padding: 16px;
    background: #FFFFFF;
    border: 1px solid #E8EAEE;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);
}

.calendar h2 {
    margin: 0 0 12px;
    font-size: 18px;
    line-height: 26px;
    font-weight: 700;
    color: #12273C;
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
    min-height: 120px;
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
    color: #374151;
    background: #E9EDF1;
    border-radius: 10px;
}

.free {
    margin: 0;
    padding: 6px 8px;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    color: #4F6D47;
    background: #E1EDDC;
    border-radius: 10px;
}
</style>
