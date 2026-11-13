export type DocumentType = 'invoice' | 'quotation' | 'receipt';
export type DocumentTheme = 'modern' | 'classic' | 'minimal' | 'bold';
export type DiscountType = 'percent' | 'fixed';

export type BusinessDetails = {
    name: string;
    email?: string | null;
    phone?: string | null;
    website?: string | null;
    address?: string | null;
    city?: string | null;
    country?: string | null;
    tax_number?: string | null;
    logo_url?: string | null;
    logo_path?: string | null;
    initials?: string;
    bank_details?: string | null;
    payment_terms?: string | null;
    notes?: string | null;
    currency?: string;
    default_tax_rate?: number;
};

export type LineItem = {
    id?: number;
    description: string;
    quantity: number | string;
    unit_price: number | string;
    tax_rate: number | string;
    line_total?: number | string;
};

export type DocumentPaperData = {
    type: DocumentType;
    number: string;
    status: string;
    theme: DocumentTheme;
    currency: string;
    issue_date: string | null;
    due_date: string | null;
    client_name: string;
    client_email?: string | null;
    client_phone?: string | null;
    client_address?: string | null;
    discount_type?: DiscountType | null;
    discount_value?: number | string | null;
    amount_paid?: number | string | null;
    notes?: string | null;
    terms?: string | null;
    business: BusinessDetails;
    items: LineItem[];
};

export type DocumentRecord = DocumentPaperData & {
    id: number;
    subtotal: string | number;
    discount_amount: string | number;
    tax_amount: string | number;
    total: string | number;
    balance: number | string;
    formatted_total: string;
    type_label: string;
    type_plural: string;
    status_label: string;
    due_label: string;
    share_url: string;
    pdf_url: string;
    public_pdf_url: string;
    whatsapp_url: string;
    mailto_url: string;
    is_overdue: boolean;
    created_at: string;
    sent_at?: string | null;
    paid_at?: string | null;
};

export type ThemeOption = {
    id: DocumentTheme;
    label: string;
    description: string;
    accent: string;
    swatches: string[];
};

export type SelectOption = {
    value: string;
    label: string;
};

export type DocumentSummary = {
    count: number;
    total: number;
    paid: number;
};
