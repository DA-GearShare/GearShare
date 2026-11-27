export type ReservationStatus = 'requested' | 'denied' | 'cancelled' | 'active' | 'returned';

export interface ReservationItem {
    id: string;
    reservationId: string;
    requestedEquipmentId: string | null;
    requestedPoolId: string | null;
    status: string;
    issuedAt: string | null;
    returnedAt: string | null;
}

export interface Reservation {
    id: string;
    requestedByUserId: string;
    responsibleUserId: string | null;
    status: ReservationStatus;
    startDatetime: string;
    endDatetime: string;
    isOverdue: boolean;
    createdAt: string;
    items: ReservationItem[];
}
