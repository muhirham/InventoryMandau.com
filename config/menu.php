<?php

return [

    // ==== REGISTRY MENU (untuk checkbox & sidebar) ====
    // key = unik; group = pengelompokan; route = nama route; icon = kelas Boxicons
    'items' => [
        // ADMIN
        [
            'key'   => 'warehouses',
            'label' => 'Warehouses',
            'route' => 'warehouses.index',
            'group' => 'admin',
            'icon'  => 'bx bx-buildings',
        ],
        [
            'key'   => 'categories',
            'label' => 'Categories',
            'route' => 'categories.index',
            'group' => 'admin',
            'icon'  => 'bx bx-category',
        ],
        [
            'key'   => 'suppliers',
            'label' => 'Suppliers',
            'route' => 'suppliers.index',
            'group' => 'admin',
            'icon'  => 'bx bx-store-alt',
        ],
        [
            'key'   => 'packages',
            'label' => 'UOM',
            'route' => 'packages.index',
            'group' => 'admin',
            'icon'  => 'bx bx-package',
        ],
        [
            'key'   => 'products',
            'label' => 'Products',
            'route' => 'products.index',
            'group' => 'admin',
            'icon'  => 'bx bx-cube-alt',
        ],
        // key: stockproducts
        [
        'key'   => 'stockproducts',
        'label' => 'Stock Products',
        'route' => 'stockproducts.index',   
        'group' => 'admin',
        'icon'  => 'bx bx-archive',
        ],

                [
            'key'   => 'stock_adjustments',
            'label' => 'Stock Adjustments',
            'route' => 'stock-adjustments.index',
            'group' => 'admin', // atau 'warehouse' kalau lu punya group itu
            'icon'  => 'bx bx-adjust',
        ],

        [
            'key'   => 'restock_request_ap',
            'label' => 'Restock Approval',
            'route' => 'stockRequest.index',
            'group' => 'admin',
            'icon'  => 'bx bx-transfer-alt',
        ],

        [
            'key'   => 'goodreceived',
            'label' => 'Goods Received',
            'route' => 'goodreceived.index',
            'group' => 'admin',
            'icon'  => 'bx bx-download',
        ],
                [
            'key'   => 'goodreceived_delete',
            'label' => 'GR Delete Requests',
            'route' => 'goodreceived.delete-requests.index',
            'group' => 'admin',
            'icon'  => 'bx bx-trash',
        ],

        [
            'key'   => 'po',
            'label' => 'Purchase Orders',
            'route' => 'po.index',
            'group' => 'admin',
            'icon'  => 'bx bx-receipt',
        ],
        [
            'key'   => 'transactions',
            'label' => 'Transactions',
            'route' => 'transactions.index', // placeholder, nanti kalau ada modulnya
            'group' => 'admin',
            'icon'  => 'bx bx-transfer',
        ],
        [
            'key'   => 'reports',
            'label' => 'Reports',
            'route' => 'reports.index',
            'group' => 'admin',
            'icon'  => 'bx bx-file',
        ],
        [
            'key'   => 'users',
            'label' => 'Users',
            'route' => 'users.index',
            'group' => 'admin',
            'icon'  => 'bx bx-user',
        ],
        [
            'key'   => 'roles',
            'label' => 'Roles & Sidebar',
            'route' => 'roles.index',
            'group' => 'admin',
            'icon'  => 'bx bx-shield-quarter',
        ],

        // WAREHOUSE
        [
            'key'   => 'wh_stocklevel',
            'label' => 'Stock Gudang',
            'route' => 'stocklevel.index', // perbaikan dari StockLevel.index
            'group' => 'warehouse',
            'icon'  => 'bx bx-layer',
        ],
        [
            'key'   => 'wh_restock',
            'label' => 'Restock Request',
            'route' => 'restocks.index',
            'group' => 'warehouse',
            'icon'  => 'bx bx-cart-add',
        ],
        [
            'key'   => 'wh_issue',
            'label' => 'Issue ke Sales (Pagi)',
            'route' => 'sales.handover.morning',
            'group' => 'warehouse',
            'icon'  => 'bx bx-up-arrow-circle',
        ],
        [
            'key'   => 'wh_reconcile',
            'label' => 'Reconcile + OTP (Sore)',
            'route' => 'sales.handover.evening',
            'group' => 'warehouse',
            'icon'  => 'bx bx-check-shield',
        ],
        [
            'key'   => 'wh_sales_reports',
            'label' => 'Sales Reports',
            'route' => 'sales.report', // disamakan dengan route sales.report
            'group' => 'warehouse',
            'icon'  => 'bx bx-bar-chart-alt-2',
        ],

        // SALES
        [
            'key'   => 'sales_daily',
            'label' => 'Daily Report',
            'route' => 'sales.report',
            'group' => 'sales',
            'icon'  => 'bx bx-calendar-check',
        ],
        [
            'key'   => 'sales_return',
            'label' => 'Return Products',
            'route' => 'sales.return',
            'group' => 'sales',
            'icon'  => 'bx bx-undo',
        ],
    ],

    // ==== LABEL GROUP UNTUK SIDEBAR ====
    'groups' => [
        'admin'     => 'Admin',
        'warehouse' => 'Warehouse',
        'sales'     => 'Sales',
    ],

    // ==== OPSI TETAP untuk Home Route combobox ====
    'home_candidates' => [
        ['label' => 'Admin Dashboard',     'route' => 'admin.dashboard'],
        ['label' => 'Warehouse Dashboard', 'route' => 'warehouse.dashboard'],
        ['label' => 'Sales Dashboard',     'route' => 'sales.dashboard'],
    ],
];
