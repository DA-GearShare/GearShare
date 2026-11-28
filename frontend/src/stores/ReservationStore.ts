import { ref } from 'vue';
import { defineStore } from 'pinia';

async function readError(res: Response, fallback: string): Promise<string> {
    try {
        const data = await res.json();
        return data.message || fallback;
    } catch {
        return fallback;
    }
}

export const useReservationStore = defineStore('reservation', () => {
    const reservations = ref();
    const reservationItems = ref();
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    async function fetchReservations() {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch("/api/v1/reservations", { credentials: 'include' });
            if (!res.ok) throw new Error('Status ' + res.status);
            const data = await res.json();
            reservations.value = data.items;
        } catch (e) {
            console.error(e);
            reservations.value = null;
            error.value = "Die Reservierungen konnten nicht geladen werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchReservationById(id: string) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch(`/api/v1/reservations/${id}`, { credentials: 'include' });
            if (!res.ok) throw new Error('Status ' + res.status);
            return await res.json();
        } catch (e) {
            console.error(e);
            error.value = "Die Reservierung konnte nicht geladen werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchReservationItems(id: string) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch(`/api/v1/reservations/${id}/items`, { credentials: 'include' });
            if (!res.ok) throw new Error('Status ' + res.status);
            const data = await res.json();
            reservationItems.value = data.items;
        } catch (e) {
            console.error(e);
            reservationItems.value = null;
            error.value = "Die Geräte der Reservierung konnten nicht geladen werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchIssueItem(id: string, itemId: string, payload: Record<string, string>) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch(`/api/v1/reservations/${id}/items/${itemId}/issue`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (!res.ok) {
                error.value = await readError(res, "Das Gerät konnte nicht als ausgegeben markiert werden.");
                return;
            }
            return await res.json();
        } catch (e) {
            console.error(e);
            error.value = "Das Gerät konnte nicht als ausgegeben markiert werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchIssueGlobal(id: string, payload: Record<string, unknown>) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch(`/api/v1/reservations/${id}/issue`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (res.status === 409) {
                const data = await res.json();
                if (data.equipment) {
                    return { conflict: true, equipment: data.equipment };
                }
                error.value = data.message || "Der Scan konnte nicht verarbeitet werden.";
                return;
            }

            if (!res.ok) {
                error.value = await readError(res, "Der Scan konnte nicht verarbeitet werden.");
                return;
            }
            return await res.json();
        } catch (e) {
            console.error(e);
            error.value = "Der Scan konnte nicht verarbeitet werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchOverdueReservations() {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch("/api/v1/reservations/overdue", { credentials: 'include' });
            if (!res.ok) throw new Error('Status ' + res.status);
            const data = await res.json();
            reservations.value = data.items;
        } catch (e) {
            console.error(e);
            reservations.value = null;
            error.value = "Die überfälligen Reservierungen konnten nicht geladen werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function rejectReservation(id: string, reason: string) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch(`/api/v1/reservations/${id}/reject`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(reason),
            });

            if (!res.ok) {
                error.value = await readError(res, "Die Reservierung konnte nicht abgelehnt werden.");
                return;
            }
            return await res.json();
        } catch (e) {
            console.error(e);
            error.value = "Die Reservierung konnte nicht abgelehnt werden.";
        } finally {
            isLoading.value = false;
        }
    }

    async function cancelReservation(id: string) {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch(`/api/v1/reservations/${id}/cancel`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(),
            });

            if (!res.ok) {
                error.value = await readError(res, "Die Reservierung konnte nicht zurückgezogen werden.");
                return;
            }
            return await res.json();
        } catch (e) {
            console.error(e);
            error.value = "Die Reservierung konnte nicht zurückgezogen werden.";
        } finally {
            isLoading.value = false;
        }
    }

    return { reservations, reservationItems, isLoading, error, fetchReservations, fetchReservationById, fetchReservationItems, fetchIssueItem, fetchIssueGlobal, fetchOverdueReservations, rejectReservation, cancelReservation };
});
