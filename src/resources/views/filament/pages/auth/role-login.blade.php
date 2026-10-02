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
            --teal: #00b4a0;
            --grey-50: #f7f8f9;
            --grey-100: #f0f1f3;
            --grey-200: #e1e3e6;
            --grey-500: #6b7280;
            --grey-700: #374151;
            --grey-900: #111827;
            background:
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(180deg, rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(110deg, rgba(0, 96, 172, .86) 0 48%, rgba(247, 248, 249, 1) 48% 100%),
                var(--navy);
            background-size: 52px 52px, 52px 52px, auto;
            color: #fff;
            min-height: 100vh;
            position: relative;
            width: 100%;
        }

        .isoc-login::before {
            background: rgba(255, 255, 255, .08);
            content: "";
            height: 1px;
            left: 0;
            position: absolute;
            top: 96px;
            width: 48%;
        }

        .isoc-login::after {
            background: rgba(0, 24, 51, .18);
            bottom: 0;
            content: "";
            left: 0;
            position: absolute;
            top: 0;
            width: 48%;
        }

        .isoc-login-shell {
            display: grid;
            gap: 64px;
            grid-template-columns: minmax(0, 1fr) minmax(420px, 480px);
            margin: 0 auto;
            max-width: 1200px;
            min-height: 100vh;
            padding: 48px 32px;
            place-items: center;
            position: relative;
            z-index: 2;
        }

        .isoc-login-brand {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
        }

        .isoc-login-logo {
            align-items: center;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 42px rgba(0, 24, 51, .2);
            display: inline-flex;
            padding: 10px 14px;
            text-decoration: none;
            width: fit-content;
        }

        .isoc-login-logo img {
            display: block;
            height: 46px;
            max-width: 190px;
            object-fit: contain;
            width: auto;
        }

        .isoc-login-kicker {
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .12em;
            margin: 48px 0 16px;
            text-transform: uppercase;
        }

        .isoc-login-title {
            color: #fff;
            font-size: clamp(44px, 5.8vw, 68px);
            font-weight: 900;
            letter-spacing: -.045em;
            line-height: 1;
            margin: 0;
            max-width: 680px;
        }

        .isoc-login-description {
            color: rgba(255, 255, 255, .76);
            font-size: 16px;
            line-height: 1.75;
            margin: 24px 0 0;
            max-width: 560px;
        }

        .isoc-login-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 34px;
        }

        .isoc-login-role {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 999px;
            color: rgba(255,255,255,.9);
            font-size: 12px;
            font-weight: 800;
            padding: 10px 14px;
        }

        .isoc-login-note {
            border-left: 3px solid var(--teal);
            color: rgba(255,255,255,.76);
            font-size: 13px;
            line-height: 1.7;
            margin-top: auto;
            max-width: 520px;
            padding-left: 16px;
        }

        .isoc-login-card {
            background: #fff;
            border: 1px solid rgba(17, 24, 39, .08);
            border-radius: 16px;
            box-shadow: 0 28px 80px rgba(0, 24, 51, .16);
            color: var(--grey-900);
            padding: 32px;
            width: 100%;
        }

        .isoc-card-head {
            border-bottom: 1px solid var(--grey-200);
            margin-bottom: 24px;
            padding-bottom: 22px;
        }

        .isoc-card-eyebrow {
            color: var(--orange);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .isoc-card-title {
            color: var(--navy);
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin: 8px 0 0;
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
            font-weight: 800;
        }

        .isoc-login-card .fi-input-wrp {
            border-color: var(--grey-200);
            border-radius: 10px;
            box-shadow: none;
            min-height: 46px;
        }

        .isoc-login-card .fi-input {
            font-size: 14px;
            min-height: 46px;
        }

        .isoc-login-card .fi-btn {
            border-radius: 10px;
            font-weight: 800;
            min-height: 46px;
        }

        .isoc-login-card .fi-btn-color-primary {
            background: var(--orange);
            box-shadow: 0 12px 26px rgba(229, 114, 0, .24);
        }

        .isoc-login-card .fi-btn-color-primary:hover {
            background: #c85f00;
        }

        .isoc-login-footer {
            align-items: center;
            color: var(--grey-500);
            display: flex;
            font-size: 12px;
            gap: 8px;
            justify-content: space-between;
            margin-top: 24px;
        }

        .isoc-login-footer a {
            color: var(--orange);
            font-weight: 800;
            text-decoration: none;
        }

        .isoc-login-footer a:hover {
            color: #c85f00;
        }

        @media (max-width: 900px) {
            .isoc-login::before,
            .isoc-login::after {
                display: none;
            }

            .isoc-login {
                background:
                    linear-gradient(180deg, var(--navy) 0 46%, var(--grey-50) 46% 100%),
                    var(--grey-50);
            }

            .isoc-login-shell {
                gap: 28px;
                grid-template-columns: 1fr;
                min-height: auto;
                padding: 28px 18px 36px;
                place-items: stretch;
            }

            .isoc-login-title {
                font-size: 40px;
            }

            .isoc-login-description {
                font-size: 15px;
            }

            .isoc-login-note {
                display: none;
            }

            .isoc-login-card {
                padding: 24px;
            }

            .isoc-login-logo img {
                height: 40px;
                max-width: 168px;
            }
        }
        </style>

        <div class="isoc-login">
            <div class="isoc-login-shell">
                <section class="isoc-login-brand" aria-label="{{ config('app.name', 'sena') }} login">
                    <a class="isoc-login-logo" href="{{ route('home') }}">
                        <img src="{{ asset('images/sena-logo.png') }}" alt="{{ config('app.name', 'sena') }}">
                    </a>

                    <p class="isoc-login-kicker">Portal Program</p>
                    <h1 class="isoc-login-title">Satu akses untuk semua panel sena.</h1>
                    <p class="isoc-login-description">
                        Masuk menggunakan akun yang sudah dibuat sistem. Setelah login, kamu akan diarahkan otomatis ke panel sesuai role akun.
                    </p>

                    <div class="isoc-login-roles" aria-label="Role yang didukung">
                        <span class="isoc-login-role">Super Admin</span>
                        <span class="isoc-login-role">Admin RTIK</span>
                        <span class="isoc-login-role">Tutor</span>
                        <span class="isoc-login-role">Peserta</span>
                    </div>

                    <p class="isoc-login-note">
                        Gunakan satu halaman login untuk masuk ke panel pusat, admin daerah, tutor, atau peserta. Sistem akan membaca role akun dan membuka panel yang sesuai.
                    </p>
                </section>

                <section class="isoc-login-card" aria-label="Form login">
                    <div class="isoc-card-head">
                        <p class="isoc-card-eyebrow">Portal Login</p>
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
                </section>
            </div>
        </div>
</div>
