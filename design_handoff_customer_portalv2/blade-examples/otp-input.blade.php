{{-- resources/views/components/otp-input.blade.php
     6-digit OTP entry with Alpine 3.6: auto-advance, backspace-back, paste-split,
     and a resend countdown. UI phase only — no server calls yet.
     Backend phase: bind `code` and submit to your Livewire OTP action. --}}

<div x-data="otpInput()" class="otp">
    <div class="otp__cells">
        <template x-for="(d, i) in digits" :key="i">
            <input
                type="text" inputmode="numeric" maxlength="1"
                class="otp__cell"
                :class="{ 'is-filled': digits[i] !== '' }"
                x-model="digits[i]"
                @input="onInput($event, i)"
                @keydown.backspace="onBackspace($event, i)"
                @paste.prevent="onPaste($event)"
                x-ref="cell"
            >
        </template>
    </div>

    <button type="button" class="btn btn--primary" :disabled="!complete">
        Verify &amp; continue
    </button>

    <p class="otp__resend">
        Didn't get the code?
        <template x-if="countdown > 0">
            <span class="otp__timer" x-text="`Resend in 0:${String(countdown).padStart(2,'0')}`"></span>
        </template>
        <template x-if="countdown === 0">
            <button type="button" class="otp__resend-btn" @click="resend()">Resend code</button>
        </template>
    </p>
</div>

<script>
    function otpInput() {
        return {
            digits: ['', '', '', '', '', ''],
            countdown: 48,
            get complete() { return this.digits.every(d => d !== ''); },
            init() { this.tick(); },
            tick() {
                const t = setInterval(() => {
                    if (this.countdown > 0) this.countdown--; else clearInterval(t);
                }, 1000);
            },
            onInput(e, i) {
                const v = e.target.value.replace(/\D/g, '');
                this.digits[i] = v.slice(-1);
                if (v && i < 5) this.$refs.cell.parentElement.children[i + 1].focus();
            },
            onBackspace(e, i) {
                if (!this.digits[i] && i > 0) {
                    this.$refs.cell.parentElement.children[i - 1].focus();
                }
            },
            onPaste(e) {
                const chars = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
                chars.forEach((c, i) => this.digits[i] = c);
            },
            resend() { this.countdown = 48; this.tick(); /* backend: request new OTP */ },
        };
    }
</script>
