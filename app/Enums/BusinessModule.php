<?php

namespace App\Enums;

enum BusinessModule: string
{
    case Catalogue = 'catalogue';
    case Inventory = 'inventory';
    case Procurement = 'procurement';
    case Customers = 'customers';
    case Sales = 'sales';
    case Finance = 'finance';
    case Reports = 'reports';
    case People = 'people';
    case Settings = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::Catalogue => 'Catalogue & Products',
            self::Inventory => 'Inventory & Warehouses',
            self::Procurement => 'Procurement & Suppliers',
            self::Customers => 'Customer Directory',
            self::Sales => 'Sales & POS',
            self::Finance => 'Finance & Accounting',
            self::Reports => 'Business Reports',
            self::People => 'Staff & People Operations',
            self::Settings => 'Business Settings',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Catalogue => 'Items, categories, brands, units, barcodes, and pricing tiers.',
            self::Inventory => 'Multi-warehouse stock, batches, serials, IMEIs, transfers, and stock movements.',
            self::Procurement => 'Suppliers, purchase orders, goods receipts, purchase bills, and supplier payments.',
            self::Customers => 'Customer directory, contact records, and credit limits.',
            self::Sales => 'POS checkout, sales orders, invoicing, payments, and quotations.',
            self::Finance => 'Accounts, ledger postings, expense vouchers, and financial tracking.',
            self::Reports => 'Analytical operational and financial report generation.',
            self::People => 'Staff scheduling, attendance tracking, and leave management.',
            self::Settings => 'Core business profile configuration and audit log inspection.',
        };
    }
}
