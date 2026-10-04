<div class="sena-auth-root">
        <style>
        .sena-auth-root {
            inset: 0;
            min-height: 100vh;
            overflow: auto;
            position: fixed;
            width: 100vw;
            z-index: 9999;
        }

        .isoc-login {
            --navy: #002d56;
            --navy-dark: #001833;
            --blue: #0060ac;
            --blue-light: #0097dc;
            --orange: #e57200;
            --orange-dark: #c85f00;
            --teal: #00b4a0;
            --grey-50: #f7f8f9;
            --grey-100: #f0f1f3;
            --grey-200: #e1e3e6;
            --grey-500: #6b7280;
            --grey-700: #374151;
            --grey-900: #111827;
            background:
                radial-gradient(900px 520px at 0% 0%, rgba(0, 151, 220, .14), transparent 60%),
                radial-gradient(760px 480px at 100% 100%, rgba(229, 114, 0, .1), transparent 60%),
                #eef2f7;
            color: var(--grey-900);
            min-height: 100vh;
            width: 100%;
        }

        .isoc-login-shell {
            align-items: center;
            display: flex;
            justify-content: center;
            margin: 0 auto;
            max-width: 1160px;
            min-height: 100vh;
            padding: 40px 32px;
        }

        .isoc-login-frame {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 40px 100px rgba(0, 24, 51, .16), 0 2px 6px rgba(0, 24, 51, .05);
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
            min-height: 640px;
            overflow: hidden;
            width: 100%;
        }

        /* Panel visual kiri */
        .isoc-login-brand {
            background:
                radial-gradient(circle at 78% 18%, rgba(0, 180, 160, .35), transparent 38%),
                radial-gradient(circle at 12% 92%, rgba(229, 114, 0, .28), transparent 40%),
                linear-gradient(155deg, var(--navy-dark) 0%, var(--navy) 45%, var(--blue) 100%);
            display: flex;
            flex-direction: column;
            isolation: isolate;
            overflow: hidden;
            padding: 36px;
            position: relative;
        }

        .isoc-login-brand::before {
            background-image: radial-gradient(rgba(255, 255, 255, .16) 1px, transparent 1px);
            background-size: 22px 22px;
            content: "";
            inset: 0;
            mask-image: linear-gradient(180deg, #000 0%, transparent 75%);
            -webkit-mask-image: linear-gradient(180deg, #000 0%, transparent 75%);
            position: absolute;
            z-index: -1;
        }

        .isoc-login-logo {
            align-items: center;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 16px 40px rgba(0, 24, 51, .28);
            display: inline-flex;
            padding: 10px 16px;
            text-decoration: none;
            transition: transform .2s ease;
            width: fit-content;
        }

        .isoc-login-logo:hover {
            transform: translateY(-2px);
        }

        .isoc-login-logo img {
            display: block;
            height: 40px;
            max-width: 170px;
            object-fit: contain;
            width: auto;
        }

        .isoc-login-orbit {
            align-items: center;
            display: flex;
            flex: 1;
            justify-content: center;
            position: relative;
        }

        .isoc-login-ring {
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 50%;
            position: absolute;
        }

        .isoc-login-ring.r1 { height: 230px; width: 230px; }
        .isoc-login-ring.r2 { border-style: dashed; height: 340px; width: 340px; animation: isoc-spin 60s linear infinite; }
        .isoc-login-ring.r3 { border-color: rgba(255, 255, 255, .07); height: 450px; width: 450px; }

        .isoc-login-dot {
            border-radius: 50%;
            position: absolute;
        }

        .isoc-login-dot.d1 { background: var(--teal); box-shadow: 0 0 0 6px rgba(0, 180, 160, .18); height: 12px; width: 12px; top: calc(50% - 175px); left: 50%; }
        .isoc-login-dot.d2 { background: var(--orange); box-shadow: 0 0 0 6px rgba(229, 114, 0, .2); height: 10px; width: 10px; top: 58%; left: calc(50% + 165px); }
        .isoc-login-dot.d3 { background: var(--blue-light); box-shadow: 0 0 0 5px rgba(0, 151, 220, .22); height: 8px; width: 8px; top: 70%; left: calc(50% - 150px); }

        .isoc-login-symbol {
            align-items: center;
            backdrop-filter: blur(6px);
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 36px;
            box-shadow: 0 30px 70px rgba(0, 12, 30, .35), inset 0 1px 0 rgba(255, 255, 255, .25);
            display: flex;
            height: 150px;
            justify-content: center;
            position: relative;
            width: 150px;
            animation: isoc-float 6s ease-in-out infinite;
        }

        .isoc-login-symbol img {
            filter: drop-shadow(0 10px 24px rgba(0, 12, 30, .35));
            height: 96px;
            width: 96px;
        }

        .isoc-login-partners {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .isoc-login-partners img {
            background: #fff;
            border-radius: 10px;
            height: 38px;
            object-fit: contain;
            padding: 6px 10px;
            width: auto;
        }

        @keyframes isoc-spin { to { transform: rotate(360deg); } }
        @keyframes isoc-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        @media (prefers-reduced-motion: reduce) {
            .isoc-login-ring.r2, .isoc-login-symbol { animation: none; }
        }

        /* Panel form kanan */
        .isoc-login-card {
            align-self: center;
            color: var(--grey-900);
            justify-self: center;
            max-width: 440px;
            padding: 48px 44px;
            width: 100%;
        }

        .isoc-card-head {
            margin-bottom: 28px;
        }

        .isoc-card-eyebrow {
            align-items: center;
            background: rgba(229, 114, 0, .1);
            border-radius: 999px;
            color: var(--orange-dark);
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            letter-spacing: .1em;
            padding: 6px 12px;
            text-transform: uppercase;
        }

        .isoc-card-eyebrow::before {
            background: var(--orange);
            border-radius: 50%;
            content: "";
            height: 6px;
            width: 6px;
        }

        .isoc-card-title {
            color: var(--navy);
            font-size: 34px;
            font-weight: 900;
            letter-spacing: -.035em;
            line-height: 1.1;
            margin: 16px 0 0;
        }

        .isoc-card-copy {
            color: var(--grey-500);
            font-size: 14px;
            line-height: 1.65;
            margin: 10px 0 0;
        }

        .isoc-login-card .fi-fo-field-wrp {
            gap: 8px;
        }

        .isoc-login-card .fi-fo-field-wrp-label span {
            color: var(--grey-700);
            font-size: 13px;
            font-weight: 700;
        }

        /* Garis input Filament dibuat dari box-shadow; tetap dipertahankan agar field terlihat. */
        .isoc-login-card .fi-input-wrp {
            background: var(--grey-50) !important;
            border-radius: 12px;
            box-shadow: inset 0 0 0 1px var(--grey-200) !important;
            min-height: 48px;
            transition: background .15s ease, box-shadow .15s ease;
        }

        .isoc-login-card .fi-input-wrp:hover {
            box-shadow: inset 0 0 0 1px #c7cbd1 !important;
        }

        .isoc-login-card .fi-input-wrp:focus-within {
            background: #fff !important;
            box-shadow: inset 0 0 0 1.5px var(--orange), 0 0 0 4px rgba(229, 114, 0, .12) !important;
        }

        .isoc-login-card .fi-input {
            color: var(--grey-900) !important;
            font-size: 14px;
            min-height: 48px;
        }

        .isoc-login-card .fi-input::placeholder {
            color: #9ca3af;
        }

        .isoc-login-card .fi-checkbox-input {
            border-radius: 5px;
            box-shadow: inset 0 0 0 1px #c7cbd1 !important;
        }

        .isoc-login-card .fi-checkbox-input:checked {
            background-color: var(--orange) !important;
            box-shadow: none !important;
        }

        .isoc-login-card .fi-btn {
            border-radius: 12px;
            font-weight: 800;
            min-height: 50px;
        }

        .isoc-login-card .fi-btn-color-primary {
            background: linear-gradient(135deg, #f08a1c 0%, var(--orange) 55%, var(--orange-dark) 100%);
            box-shadow: 0 14px 30px rgba(229, 114, 0, .28);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .isoc-login-card .fi-btn-color-primary:hover {
            box-shadow: 0 18px 36px rgba(229, 114, 0, .34);
            transform: translateY(-1px);
        }

        .isoc-login-footer {
            align-items: center;
            border-top: 1px solid var(--grey-200);
            color: var(--grey-500);
            display: flex;
            font-size: 13px;
            gap: 8px;
            justify-content: center;
            margin-top: 28px;
            padding-top: 20px;
        }

        .isoc-login-footer a {
            color: var(--orange-dark);
            font-weight: 800;
            text-decoration: none;
        }

        .isoc-login-footer a:hover {
            text-decoration: underline;
        }

        .isoc-login-copy {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 18px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .isoc-login-shell {
                align-items: flex-start;
                padding: 16px;
            }

            .isoc-login-frame {
                border-radius: 22px;
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .isoc-login-brand {
                align-items: center;
                flex-direction: row;
                justify-content: space-between;
                min-height: 0;
                padding: 22px;
            }

            .isoc-login-orbit {
                flex: 0 0 auto;
            }

            .isoc-login-ring, .isoc-login-dot, .isoc-login-partners {
                display: none;
            }

            .isoc-login-symbol {
                animation: none;
                border-radius: 20px;
                height: 64px;
                width: 64px;
            }

            .isoc-login-symbol img {
                height: 40px;
                width: 40px;
            }

            .isoc-login-logo img {
                height: 32px;
            }

            .isoc-login-card {
                padding: 28px 22px 26px;
            }

            .isoc-card-title {
                font-size: 28px;
            }
        }
        </style>

        <div class="isoc-login">
            <div class="isoc-login-shell">
                <div class="isoc-login-frame">
                    <section class="isoc-login-brand" aria-label="{{ config('app.name', 'sena') }}">
                        <a class="isoc-login-logo" href="{{ route('home') }}">
                            <img src="{{ asset('images/sena-logo.png') }}" alt="{{ config('app.name', 'sena') }}">
                        </a>

                        <div class="isoc-login-orbit" aria-hidden="true">
                            <span class="isoc-login-ring r3"></span>
                            <span class="isoc-login-ring r2"></span>
                            <span class="isoc-login-ring r1"></span>
                            <span class="isoc-login-dot d1"></span>
                            <span class="isoc-login-dot d2"></span>
                            <span class="isoc-login-dot d3"></span>
                            <div class="isoc-login-symbol">
                                <img src="{{ asset('images/sena-symbol.png') }}" alt="">
                            </div>
                        </div>

                        <div class="isoc-login-partners" aria-label="Mitra">
                            <img src="{{ asset('images/partners/isoc-jakarta.png') }}" alt="ISOC Indonesia Jakarta Chapter">
                            <img src="{{ asset('images/partners/komdigi.png') }}" alt="Kementerian Komunikasi dan Digital">
                            <img src="{{ asset('images/partners/relawan-tik.png') }}" alt="Relawan TIK Indonesia">
                        </div>
                    </section>

                    <section class="isoc-login-card" aria-label="Form login">
                        <div class="isoc-card-head">
                            <span class="isoc-card-eyebrow">Portal Login</span>
                            <h2 class="isoc-card-title">Masuk ke sena</h2>
                            <p class="isoc-card-copy">Gunakan email dan password akun {{ config('app.name', 'sena') }}. Sistem akan memilih panel yang tepat untuk kamu.</p>
                        </div>

                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                        <x-filament-panels::form id="form" wire:submit="authenticate">
                            {{ $this->form }}

                            <x-filament-panels::form.actions
                                :actions="$this->getCachedFormActions()"
                                :full-width="$this->hasFullWidthFormActions()"
                            />
                        </x-filament-panels::form>

                        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

                        <div class="isoc-login-footer">
                            <span>Belum punya akun?</span>
                            <a href="{{ route('events') }}">Daftar lewat event</a>
                        </div>

                        <p class="isoc-login-copy">&copy; {{ now()->year }} ISOC Indonesia Jakarta Chapter</p>
                    </section>
                </div>
            </div>
        </div>
</div>
