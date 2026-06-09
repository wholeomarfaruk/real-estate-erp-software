<div
    x-data="{ sendModal: $wire.entangle('sendModal') }"
    x-init="$store.pageName = { name: 'Messages', slug: 'marketing' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--mono:'IBM Plex Mono',monospace;font-family:'Inter',system-ui,sans-serif;color:var(--ink-1);background:var(--canvas);"
    class="min-h-screen">

    {{-- Header --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <div style="font-size:11px;color:var(--ink-3);font-family:var(--mono);display:flex;gap:6px;align-items:center;margin-bottom:6px;">
                <span>Marketing</span><span style="opacity:.4">/</span><span style="color:var(--ink-1)">Messages</span>
            </div>
            <h1 style="font-size:24px;font-weight:600;letter-spacing:-.01em;margin:0;">Message Log</h1>
            <p style="margin-top:4px;font-size:13px;color:var(--ink-2);">All sent messages — campaign and individual.</p>
        </div>
        @can('marketing.message.send')
        <button wire:click="openSendModal"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white cursor-pointer border-0"
            style="background:var(--ink-1);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9l20-7z"/></svg>
            Send Message
        </button>
        @endcan
    </div>

    <div style="padding:20px 24px 80px;">
        {{-- KPI --}}
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1px;background:var(--rule);border:1px solid var(--rule);border-radius:10px;overflow:hidden;margin-bottom:20px;">
            @foreach([['label'=>'Total','value'=>$kpi['total'],'fg'=>'var(--ink-1)'],['label'=>'Sent','value'=>$kpi['sent'],'fg'=>'#065F46'],['label'=>'Failed','value'=>$kpi['failed'],'fg'=>'#991B1B'],['label'=>'SMS','value'=>$kpi['sms'],'fg'=>'#1D4ED8'],['label'=>'Email','value'=>$kpi['email'],'fg'=>'#7C3AED']] as $s)
            <div style="background:var(--paper);padding:14px 18px;">
                <div style="font:600 10px 'Inter',sans-serif;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);">{{ $s['label'] }}</div>
                <div style="margin-top:5px;font:600 22px var(--mono);color:{{ $s['fg'] }};">{{ $s['value'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <div style="position:relative;flex:1;min-width:180px;">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ink-3);" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search recipient or content…"
                    style="width:100%;padding:7px 10px 7px 32px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;box-sizing:border-box;">
            </div>
            <select wire:model.live="filterType" style="padding:7px 10px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                <option value="all">All Types</option>
                <option value="sms">SMS</option>
                <option value="email">Email</option>
            </select>
            <select wire:model.live="filterStatus" style="padding:7px 10px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                <option value="all">All Status</option>
                <option value="queued">Queued</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="failed">Failed</option>
                <option value="opened">Opened</option>
            </select>
        </div>

        {{-- Table --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas);border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Recipient</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Type</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Message</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Campaign</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Status</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Sent At</th>
                        <th style="padding:10px 16px;text-align:center;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                    @php
                        $statusColors = [
                            'queued'    => 'background:#FEF3C7;color:#92400E;',
                            'sent'      => 'background:#D1FAE5;color:#065F46;',
                            'delivered' => 'background:#DBEAFE;color:#1D4ED8;',
                            'failed'    => 'background:#FEE2E2;color:#991B1B;',
                            'opened'    => 'background:#EDE9FE;color:#6D28D9;',
                        ];
                        $typeColors = ['sms'=>'background:#DBEAFE;color:#1D4ED8;','email'=>'background:#EDE9FE;color:#6D28D9;'];
                    @endphp
                    <tr style="border-bottom:1px solid var(--rule);">
                        <td style="padding:12px 16px;">
                            <div style="font:500 13px var(--mono);color:var(--ink-1);">{{ $msg->recipient }}</div>
                            @if($msg->sentByUser)
                            <div style="font-size:11px;color:var(--ink-3);margin-top:2px;">by {{ $msg->sentByUser->name }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $typeColors[$msg->type] ?? '' }}">{{ strtoupper($msg->type) }}</span>
                        </td>
                        <td style="padding:12px 16px;max-width:260px;">
                            @if($msg->subject)
                            <div style="font-weight:600;font-size:12px;color:var(--ink-1);margin-bottom:2px;">{{ $msg->subject }}</div>
                            @endif
                            <div style="font-size:12px;color:var(--ink-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit($msg->body, 80) }}</div>
                        </td>
                        <td style="padding:12px 16px;">
                            @if($msg->campaign)
                            <div style="font-size:12px;color:var(--ink-2);">{{ Str::limit($msg->campaign->name, 30) }}</div>
                            @else
                            <span style="color:var(--ink-3);font-size:11px;">Individual</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $statusColors[$msg->status] ?? '' }}">{{ ucfirst($msg->status) }}</span>
                        </td>
                        <td style="padding:12px 16px;">
                            @if($msg->sent_at)
                            <div style="font:11px var(--mono);color:var(--ink-2);">{{ $msg->sent_at->format('d M Y') }}</div>
                            <div style="font:10px var(--mono);color:var(--ink-3);">{{ $msg->sent_at->format('H:i') }}</div>
                            @else
                            <span style="color:var(--ink-3);font-size:11px;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;text-align:center;">
                            @if($msg->type === 'sms' && $msg->status === 'sent' && $msg->provider_message_id)
                            <button wire:click="checkDeliveryStatus({{ $msg->id }})"
                                wire:loading.attr="disabled"
                                style="padding:4px 8px;border-radius:5px;border:none;font:500 11px 'Inter',sans-serif;background:#DBEAFE;color:#1D4ED8;cursor:pointer;transition:all;"
                                onmouseover="this.style.background='#BFDBFE'"
                                onmouseout="this.style.background='#DBEAFE'"
                                wire:loading.style="opacity:0.6">
                                <span wire:loading.remove>Check Status</span>
                                <span wire:loading wire:target="checkDeliveryStatus">Checking...</span>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="padding:48px;text-align:center;color:var(--ink-3);">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($messages->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--rule);background:var(--canvas);">{{ $messages->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Send Modal --}}
    <div x-show="sendModal" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div @click="$wire.sendModal = false" style="position:absolute;inset:0;background:rgba(0,0,0,.5);"></div>
        <div x-show="sendModal"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             style="position:relative;width:100%;max-width:560px;background:var(--paper);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;">

            <div style="padding:18px 24px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;">
                <div style="font-weight:600;font-size:16px;">Send Individual Message</div>
                <button @click="$wire.sendModal = false" style="background:none;border:none;cursor:pointer;color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding:24px;">
                <form wire:submit="sendMessage">
                    <div class="flex flex-col gap-4">
                        {{-- Type --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Message Type</label>
                            <div class="flex gap-2">
                                @foreach(['sms'=>'SMS','email'=>'Email'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model.live="sType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Template --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Load Template (optional)</label>
                            <select wire:model.live="sTemplateId" style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                                <option value="">Select template…</option>
                                @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Member --}}
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Member Type</label>
                                <select wire:model.live="sMemberType" style="width:100%;padding:8px 10px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                                    <option value="lead">Lead</option>
                                    <option value="customer">Customer</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">Select Member</label>
                                <select wire:model.live="sMemberId" style="width:100%;padding:8px 10px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                                    <option value="">Select…</option>
                                    @if($sMemberType === 'lead')
                                        @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}">{{ $lead->name }} — {{ $lead->phone }}</option>
                                        @endforeach
                                    @else
                                        @foreach($customers as $cust)
                                        <option value="{{ $cust->id }}">{{ $cust->name }} — {{ $cust->phone }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        {{-- Recipient --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Recipient <span class="text-red-500">*</span></label>
                            <input wire:model="sRecipient" type="text" placeholder="Phone or email address"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px var(--mono);background:var(--canvas);outline:none;box-sizing:border-box;">
                            @error('sRecipient') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Subject (email) --}}
                        <div x-show="$wire.sType === 'email'">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Subject</label>
                            <input wire:model="sSubject" type="text" placeholder="Email subject"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;box-sizing:border-box;">
                        </div>

                        {{-- Body --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Message Body <span class="text-red-500">*</span></label>
                            <textarea wire:model="sBody" rows="5" placeholder="Type your message…"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;resize:vertical;box-sizing:border-box;"></textarea>
                            @error('sBody') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$wire.sendModal = false"
                                style="padding:9px 20px;border:1px solid var(--rule);border-radius:7px;font:600 12px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-2);cursor:pointer;">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled"
                                style="padding:9px 24px;background:var(--ink-1);color:white;border:none;border-radius:7px;font:600 12px 'Inter',sans-serif;cursor:pointer;">
                                <span wire:loading.remove wire:target="sendMessage">Send Now</span>
                                <span wire:loading wire:target="sendMessage">Sending…</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
