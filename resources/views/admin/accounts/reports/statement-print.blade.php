<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement Sheet Print</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .page-wrap {
            max-width: 1380px;
            margin: 0 auto;
            padding: 24px;
        }

        .statement-table {
            width: 100%;
            border-collapse: collapse;
        }

        .statement-table th,
        .statement-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            font-size: 12px;
            white-space: nowrap;
            vertical-align: top;
        }

        .statement-table thead th,
        .statement-table tfoot th {
            background: #f9fafb;
        }

        .flex {
            display: flex;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        .items-start {
            align-items: flex-start;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-4 {
            gap: 16px;
        }

        .grid {
            display: grid;
            gap: 16px;
        }

        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .md\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .xl\:grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .overflow-x-auto {
            overflow-x: auto;
        }

        .rounded-2xl {
            border-radius: 18px;
        }

        .border,
        .border-gray-100,
        .border-gray-200,
        .border-gray-300,
        .border-amber-200,
        .border-slate-200,
        .border-emerald-200,
        .border-indigo-200 {
            border: 1px solid #e5e7eb;
        }

        .border-gray-100 {
            border-color: #f3f4f6;
        }

        .border-gray-200 {
            border-color: #e5e7eb;
        }

        .border-gray-300 {
            border-color: #d1d5db;
        }

        .border-amber-200 {
            border-color: #fde68a;
        }

        .border-slate-200 {
            border-color: #cbd5e1;
        }

        .border-emerald-200 {
            border-color: #a7f3d0;
        }

        .border-indigo-200 {
            border-color: #c7d2fe;
        }

        .border-dashed {
            border-style: dashed;
        }

        .border-b {
            border-bottom: 1px solid #e5e7eb;
        }

        .border-t {
            border-top: 1px solid #e5e7eb;
        }

        .bg-white {
            background: #ffffff;
        }

        .bg-gray-50 {
            background: #f9fafb;
        }

        .bg-amber-50 {
            background: #fffbeb;
        }

        .bg-slate-50 {
            background: #f8fafc;
        }

        .bg-emerald-50 {
            background: #ecfdf5;
        }

        .bg-indigo-50 {
            background: #eef2ff;
        }

        .p-5 {
            padding: 20px;
        }

        .px-4 {
            padding-left: 16px;
            padding-right: 16px;
        }

        .px-5 {
            padding-left: 20px;
            padding-right: 20px;
        }

        .py-3 {
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .py-4 {
            padding-top: 16px;
            padding-bottom: 16px;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mt-5 {
            margin-top: 20px;
        }

        .min-w-\[220px\] {
            min-width: 220px;
        }

        .text-xs {
            font-size: 11px;
        }

        .text-sm {
            font-size: 13px;
        }

        .text-lg {
            font-size: 18px;
        }

        .text-xl {
            font-size: 24px;
        }

        .text-2xl {
            font-size: 28px;
        }

        .font-medium {
            font-weight: 500;
        }

        .font-semibold {
            font-weight: 600;
        }

        .font-bold {
            font-weight: 700;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .tracking-\[0\.24em\] {
            letter-spacing: 0.24em;
        }

        .tracking-wide {
            letter-spacing: 0.08em;
        }

        .text-gray-400 {
            color: #9ca3af;
        }

        .text-gray-300 {
            color: #d1d5db;
        }

        .text-gray-500 {
            color: #6b7280;
        }

        .text-gray-600 {
            color: #4b5563;
        }

        .text-gray-700 {
            color: #374151;
        }

        .text-gray-800 {
            color: #1f2937;
        }

        .text-gray-900 {
            color: #111827;
        }

        .text-slate-500 {
            color: #64748b;
        }

        .text-slate-900 {
            color: #0f172a;
        }

        .text-amber-600 {
            color: #d97706;
        }

        .text-amber-700 {
            color: #b45309;
        }

        .text-amber-800 {
            color: #92400e;
        }

        .text-emerald-600 {
            color: #059669;
        }

        .text-emerald-700 {
            color: #047857;
        }

        .text-indigo-600 {
            color: #4f46e5;
        }

        .text-indigo-700 {
            color: #4338ca;
        }

        .text-rose-600 {
            color: #e11d48;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mx-2 {
            margin-left: 8px;
            margin-right: 8px;
        }

        .shadow-sm {
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .space-y-6 > * + * {
            margin-top: 24px;
        }

        .grid {
            display: grid;
            gap: 16px;
        }

        .md-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .xl-grid-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page-wrap {
                max-width: none;
                padding: 0;
            }

            .statement-sheet-card {
                break-inside: avoid;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="page-wrap">
        @include('admin.accounts.reports.partials.statement-sheet', ['report' => $report])
    </div>
</body>
</html>
