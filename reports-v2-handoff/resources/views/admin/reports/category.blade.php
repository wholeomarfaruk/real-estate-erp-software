{{--
    resources/views/admin/reports/category.blade.php
    Reports category detail page — Admin · Star Unity ERP
    Stack: Tailwind CSS 4 + Alpine.js 3.x

    Route: /admin/reports/{category}  → admin.reports.category
    The $category object is passed from the controller (see routes/web.php).
    Clicking a category pill navigates to a new route (wire:navigate for SPA feel).
--}}
@extends('layouts.app')

@section('title', $category['name'])

@section('content')
<div class="max-w-[1100px] mx-auto px-6 py-7">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-1.5 text-[11.5px] font-mono text-ink-3 mb-3.5">
        <a href="#" class="hover:text-ink-1 transition-colors">Admin</a>
        <span class="opacity-50">/</span>
        <a href="{{ route('admin.reports') }}" class="hover:text-ink-1 transition-colors" wire:navigate>Reports</a>
        <span class="opacity-50">/</span>
        <span class="text-ink-1">{{ $category['name'] }}</span>
    </div>

    {{-- Category hero --}}
    <div class="flex items-center gap-5 bg-paper border border-rule rounded-[14px] px-6 py-5 mb-5">
        <div class="w-16 h-16 rounded-2xl flex-shrink-0 flex items-center justify-center t-{{ $category['key'] }}">
            {!! $category['icon'] !!}
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-[21px] font-semibold tracking-tight">{{ $category['name'] }}</h1>
            <div class="mt-1 flex items-center gap-2 text-[12px] font-mono text-ink-2">
                <span>{{ count($category['items']) }} reports</span>
                <span class="w-[3px] h-[3px] rounded-full bg-ink-3"></span>
                <span>Reports module</span>
            </div>
            <div class="mt-1.5 text-[13px] text-ink-2 leading-snug">{{ $category['desc'] }}</div>
        </div>
        <a href="{{ route('admin.reports') }}" wire:navigate class="btn flex-shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            All reports
        </a>
    </div>

    {{-- Report cards — 3 col grid --}}
    <div class="grid grid-cols-3 gap-3 mb-10">
        @foreach ($category['items'] as $item)
            <a class="rep-card" href="{{ $item['route'] ?? '#' }}">
                <div class="rc-ic t-{{ $category['key'] }}">
                    {!! $category['icon'] !!}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="rc-name">{{ $item['name'] }}</div>
                    <div class="rc-desc">{{ $item['desc'] }}</div>
                </div>
                <span class="rc-chev">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            </a>
        @endforeach
    </div>

    {{-- Browse other categories --}}
    <div>
        <div class="text-[10.5px] font-mono text-ink-3 uppercase tracking-widest mb-3">Browse categories</div>
        <div class="flex flex-wrap gap-2">
            @foreach ($allCategories as $cat)
                <a href="{{ route('admin.reports.category', $cat['key']) }}"
                   wire:navigate
                   class="other-pill @if($cat['key'] === $category['key']) active-pill @endif">
                    <span class="op-ic t-{{ $cat['key'] }}">{!! $cat['icon'] !!}</span>
                    {{ str_replace(' Reports', '', $cat['name']) }}
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection
