export interface CustomerNotification {
    id: string;
    type: string;
    title: string;
    message: string;
    action_url?: string | null;
    action_label?: string | null;
    tone: 'neutral' | 'success' | 'danger';
    read_at?: string | null;
    created_at?: string | null;
}
