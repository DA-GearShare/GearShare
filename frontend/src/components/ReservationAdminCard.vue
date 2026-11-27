<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useUsersStore } from '@/stores/UsersStore.ts';
import { useEquipmentStore, type EquipmentPhoto } from "@/stores/EquipmentStore.ts";
import type { Reservation } from '@/types/reservation';
import { formatRange, formatTime } from '@/utils/dateFormat.ts';
import RejectModal from "@/components/RejectModal.vue";

const props = defineProps<{
    reservation: Reservation;
}>();

const emit = defineEmits<{
    open: [id: string];
}>();

const photos = ref<(EquipmentPhoto | null)[]>([]);
const equipmentStore = useEquipmentStore();
const userStore = useUsersStore();
const username = ref('…');
const showReject = ref(false);

const shownItems = computed(() => {
    return props.reservation.items.slice(0, 3);
});

onMounted(async () => {
    const user = await userStore.fetchUserById(props.reservation.requestedByUserId);
    username.value = user?.name ?? props.reservation.requestedByUserId;
    photos.value = await Promise.all(
        shownItems.value.map(item => {
            if (!item.requestedEquipmentId) return null;
            return equipmentStore.fetchMainPhoto(item.requestedEquipmentId);
        })
    );
});

function RejectPopUp() {
    showReject.value = true;
}
function closeScan() {
    showReject.value = false;
}
</script>

<template>
    <article class="card reservation">
        <div class="top">
            <div class="date-box">
                <strong>{{ formatRange(reservation.startDatetime, reservation.endDatetime) }}</strong>
                <span class="meta">{{ formatTime(reservation.startDatetime) }}–{{ formatTime(reservation.endDatetime) }}</span>
                <span class="user">{{ username }}</span>
            </div>

            <div class="thumbs">
                <div v-for="(item, i) in shownItems" :key="item.id" class="thumb-tile">
                    <img
                        v-if="photos[i]"
                        :src="photos[i]?.url"
                        alt="Gerätefoto"
                        class="thumb-image"
                    />
                    <span v-else>IMG</span>
                </div>
            </div>
        </div>

        <RejectModal
            v-if="showReject"
            title="Reservierung ablehnen"
            :id=reservation.id
            @close="closeScan"
        />

        <div class="bottom">
            <span class="hint">
                <button @click="RejectPopUp" class="btn-secondary reject">ablehnen</button>
            </span>
            <button type="button" class="open-button" @click="emit('open', reservation.id)">
                <span>{{ reservation.items.length }} Geräte</span>
                <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </article>
</template>

<style scoped>
.reservation {
    padding: var(--space-m);
}

.top {
    display: flex;
    gap: var(--space-s);
}

.date-box {
    display: flex;
    flex-direction: column;
    width: 150px;
    gap: var(--space-xs);
    flex-shrink: 0;
    padding: var(--space-m);
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.user {
    font-size: var(--text-label-size);
}

.thumbs {
    display: flex;
    flex: 1;
    gap: var(--space-s);
    min-width: 0;
}

.thumb-tile {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    min-width: 0;
    max-width: 100px;
    font-size: var(--text-help-size);
    color: var(--color-muted);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: var(--space-s);
}

.open-button {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    padding: 0;
    font: inherit;
    font-size: var(--text-label-size);
    font-weight: var(--font-bold);
    color: var(--color-accent);
    background: none;
    border: none;
    cursor: pointer;
}

.chevron-icon {
    transition: transform 0.15s;
}

.reject {
    min-height: 0;
    padding: 6px var(--space-m);
    font-size: var(--text-label-size);
    line-height: 1.2;
}
</style>
