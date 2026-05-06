<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement Sheet PDF</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
        }

        .statement-table {
            width: 100%;
            border-collapse: collapse;
        }

        .statement-table th,
        .statement-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            font-size: 11px;
            white-space: nowrap;
            vertical-align: top;
        }

        .statement-table thead th,
        .statement-table tfoot th {
            background: #f9fafb;
        }

        .flex {
            display: block;
        }

        .flex-wrap,
        .items-start,
        .justify-between {
            display: block;
        }

        .gap-4 {
            margin-bottom: 12px;
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
            padding: 16px;
        }

        .px-4 {
            padding-left: 12px;
            padding-right: 12px;
        }

        .px-5 {
            padding-left: 16px;
            padding-right: 16px;
        }

        .py-3 {
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .py-4 {
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-4 {
            margin-top: 12px;
        }

        .mt-5 {
            margin-top: 16px;
        }

        .min-w-\[220px\] {
            min-width: 220px;
        }

        .text-xs {
            font-size: 10px;
        }

        .text-sm {
            font-size: 11px;
        }

        .text-lg {
            font-size: 16px;
        }

        .text-xl {
            font-size: 20px;
        }

        .text-2xl {
            font-size: 22px;
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
            letter-spacing: 0.18em;
        }

        .tracking-wide {
            letter-spacing: 0.06em;
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
            box-shadow: none;
        }

        .space-y-6 > * + * {
            margin-top: 20px;
        }

        .grid {
            width: 100%;
            font-size: 0;
        }

        .grid > div {
            display: inline-block;
            width: 24%;
            margin-right: 1%;
            vertical-align: top;
            font-size: 11px;
        }

        .grid > div:last-child {
            margin-right: 0;
        }
    </style>
</head>
<body>
    @include('admin.accounts.reports.partials.statement-sheet', ['report' => $report])
</body>
</html>
