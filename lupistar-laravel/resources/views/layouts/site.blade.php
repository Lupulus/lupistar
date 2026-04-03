<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Lupistar' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/style-navigation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/film-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/scroll-to-top.css') }}">
    @yield('styles')
</head>
<body>
    <x-film-modal />
    <div class="background"></div>
    <header>
        <x-navbar />
    </header>

    @yield('content')

    <x-footer />
    <x-scroll-to-top />

    <style>
        .custom-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(3px);
        }

        .custom-popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .custom-popup {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 15px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid #444;
            transform: scale(0.7) translateY(-50px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .custom-popup-overlay.show .custom-popup {
            transform: scale(1) translateY(0);
        }

        .custom-popup::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b35, #f7931e, #ffd23f);
        }

        .custom-popup-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .custom-popup-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
            font-weight: bold;
        }

        .custom-popup-icon.confirm {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
        }

        .custom-popup-icon.alert {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .custom-popup-icon.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .custom-popup-title {
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            flex: 1;
        }

        .custom-popup-message {
            color: #ccc;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 25px;
            text-align: left;
            white-space: pre-line;
        }

        .custom-popup-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .custom-popup-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 80px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-popup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .custom-popup-btn.primary {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
        }

        .custom-popup-btn.primary:hover {
            background: linear-gradient(135deg, #e55a2b, #e8841a);
        }

        .custom-popup-btn.secondary {
            background: #444;
            color: #ccc;
            border: 1px solid #666;
        }

        .custom-popup-btn.secondary:hover {
            background: #555;
            color: #fff;
            border-color: #777;
        }

        .custom-popup-btn.danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .custom-popup-btn.danger:hover {
            background: linear-gradient(135deg, #d62c1a, #a93226);
        }

        .custom-popup-btn.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .custom-popup-btn.success:hover {
            background: linear-gradient(135deg, #229954, #28b463);
        }

        @media (max-width: 768px) {
            .custom-popup {
                margin: 20px;
                padding: 25px;
                max-width: none;
                width: calc(100% - 40px);
            }

            .custom-popup-buttons {
                flex-direction: column;
            }

            .custom-popup-btn {
                width: 100%;
                margin-bottom: 8px;
            }

            .custom-popup-btn:last-child {
                margin-bottom: 0;
            }
        }

        .custom-popup-btn:focus {
            outline: 2px solid #ff6b35;
            outline-offset: 2px;
        }

        .custom-popup-field {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .custom-popup-input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid #666;
            color: #fff;
            font-size: 15px;
        }

        .custom-popup-input:focus {
            outline: 2px solid #ff6b35;
            outline-offset: 2px;
            border-color: #ff6b35;
        }

        .custom-popup-error {
            color: #ffb4b4;
            font-size: 13px;
        }
    </style>

    <div id="custom-popup-overlay" class="custom-popup-overlay">
        <div class="custom-popup" role="dialog" aria-modal="true">
            <div class="custom-popup-header">
                <div id="custom-popup-icon" class="custom-popup-icon">
                    <span id="custom-popup-icon-text">?</span>
                </div>
                <h3 id="custom-popup-title" class="custom-popup-title">Confirmation</h3>
            </div>
            <div id="custom-popup-message" class="custom-popup-message"></div>
            <div id="custom-popup-buttons" class="custom-popup-buttons"></div>
        </div>
    </div>

    <script src="{{ asset('scripts-js/profile-image-persistence.js') }}" defer></script>
    <script src="{{ asset('scripts-js/background.js') }}" defer></script>
    <script src="{{ asset('scripts-js/carousel-recentfilm.js') }}" defer></script>
    <script src="{{ asset('scripts-js/film-modal.js') }}" defer></script>
    <script src="{{ asset('scripts-js/notification-badge.js') }}" defer></script>
    <script src="{{ asset('scripts-js/custom-popup.js') }}" defer></script>
    <script src="{{ asset('scripts-js/scroll-to-top.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const isLoggedIn = @json((bool) session()->get('user_id'));
                if (!isLoggedIn) return;

                const res = await fetch(@json(route('privacy.status')), { credentials: 'same-origin' });
                const data = await res.json();
                if (!data?.should_show) return;
                if (!window.popupManager?.show) return;

                const messageParts = [];
                if (data.message) messageParts.push(data.message);
                if (data.updated_at) messageParts.push(`Date : ${data.updated_at}`);
                messageParts.push('Cliquez sur "Voir" pour lire la politique de confidentialité.');

                const confirmed = await window.popupManager.show({
                    type: 'confirm',
                    title: 'Mise à jour - Politique de confidentialité',
                    message: messageParts.join('\n\n'),
                    confirmText: 'Voir',
                    cancelText: 'Plus tard',
                    showCancel: true,
                    confirmClass: 'primary',
                });

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                if (token) {
                    await fetch(@json(route('privacy.ack')), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ version: data.current_version }),
                    });
                }

                if (!confirmed) return;

                if (data.policy_url) {
                    window.location.href = data.policy_url;
                }
            } catch (e) {
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
