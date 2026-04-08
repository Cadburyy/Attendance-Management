<!doctype html>
@php
use App\Models\Setting;
$settings = cache()->remember('app_settings', 60, fn() => Setting::pluck('value', 'key')->toArray());

$brand = $settings['brand_name'] ?? 'Company';
$font = $settings['font'] ?? 'Nunito';
$logoUrl = !empty($settings['logo_path']) ? asset('storage/'.str_replace('\\', '/', $settings['logo_path'])) : asset('images/logo.png');
$faviconUrl = !empty($settings['favicon_path']) ? asset('storage/'.str_replace('\\', '/', $settings['favicon_path'])) : asset('favicon.ico');
@endphp

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brand }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link href="https://fonts.bunny.net/css?family={{ str_replace(' ', '+', $font) }}:400,600,700" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        :root {
            --sb-bg: linear-gradient(135deg, #172554 0%, #1E3A8A 100%);
            --sb-width: 260px;
            --sb-collapsed: 80px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: '{{ $font }}', sans-serif; background: #f5f7fb; color: #2d3748; min-height: 100vh; overflow-x: hidden; }

        .sidebar {
            position: fixed; left: 0; top: 0; height: 100vh; width: var(--sb-width);
            background: var(--sb-bg); color: white; z-index: 1060;
            display: flex; flex-direction: column; transition: var(--transition);
            box-shadow: 4px 0 20px rgba(0,0,0,0.15); overflow: hidden;
        }
        
        .sidebar.collapsed { width: var(--sb-collapsed); box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        
        .sidebar-header { 
            padding: 16px 14px; 
            display: flex; 
            align-items: center; 
            min-height: 85px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            white-space: nowrap; 
        }
        
        .sidebar-logo { 
            width: 52px; 
            height: 52px; 
            border-radius: 12px; 
            background: #fff; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-shrink: 0; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
        }
        .sidebar-logo img { width: 85%; height: 85%; object-fit: contain; }
        
        .sidebar-brand { 
            margin-left: 15px;
            font-size: 18px; 
            font-weight: 700; 
            transition: opacity 0.2s; 
            opacity: 1; 
        }

        .sidebar.collapsed .sidebar-brand { opacity: 0; pointer-events: none; }

        .sidebar-nav { flex: 1; padding: 20px 0; list-style: none; overflow-y: auto; overflow-x: hidden; }
        .nav-link {
            display: flex; align-items: center; padding: 14px 28px;
            color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; 
            transition: var(--transition); white-space: nowrap;
        }
        .nav-link i { width: 24px; text-align: center; font-size: 18px; margin-right: 16px; flex-shrink: 0; }
        .nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .nav-link.active { background: rgba(255,255,255,0.15); color: #fff; border-left: 4px solid #60A5FA; padding-left: 24px; font-weight: 600; }

        .nav-link-text { transition: opacity 0.2s; opacity: 1; }
        .sidebar.collapsed .nav-link-text { opacity: 0; pointer-events: none; }

        .sidebar-footer { padding: 16px 14px; border-top: 1px solid rgba(255,255,255,0.1); position: relative; }
        
        .user-menu-toggle {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px; width: 100%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; color: white; cursor: pointer; transition: all 0.3s ease;
        }
        .user-menu-toggle:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.25); }

        .user-menu-toggle span { transition: opacity 0.2s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .user-dropdown {
            position: absolute; bottom: 85px; left: 14px; right: 14px; background: #fff;
            border-radius: 10px; list-style: none; display: none;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15); z-index: 1070; overflow: hidden;
        }
        .user-dropdown.show { display: block; animation: slideUp 0.3s ease; }
        
        .dropdown-item { 
            padding: 14px 16px; color: #ef4444; border: none; background: transparent; 
            width: 100%; text-align: left; cursor: pointer; font-weight: 700; 
            display: flex; align-items: center; gap: 10px; transition: all 0.2s ease; 
        }
        .dropdown-item:hover { background: #fff; color: #dc2626; transform: scale(1.02); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2); }

        main { margin-left: var(--sb-collapsed); padding: 32px; transition: none; }

        .nav-backdrop {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px);
            z-index: 1050; opacity: 0; pointer-events: none;
            transition: opacity 0.4s ease, padding-left 0.3s ease;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        
        .nav-backdrop.active { 
            opacity: 1; 
            padding-left: var(--sb-width); 
        }

        .center-brand-display { text-align: center; transform: translateY(20px); transition: 0.5s ease; opacity: 0; }
        .nav-backdrop.active .center-brand-display { transform: translateY(0); opacity: 1; }

        .backdrop-logo { width: 120px; height: 120px; background: white; border-radius: 24px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 20px; display: inline-block; }
        .backdrop-logo img { width: 100%; height: 100%; object-fit: contain; }
        .backdrop-tagline { color: white; font-size: 24px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.3); letter-spacing: 1px; }

        .mobile-toggle-btn { 
            display: none; 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            z-index: 1100; 
            background: #1E3A8A; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            width: 44px; 
            height: 44px; 
            align-items: center; 
            justify-content: center; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
            transition: background 0.3s;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (min-width: 769px) {
            .sidebar.collapsed .user-menu-toggle span,
            .sidebar.collapsed .user-menu-toggle i:last-child { 
                opacity: 0; 
                pointer-events: none;
            }
        }

        @media (max-width: 768px) {
            .mobile-toggle-btn { display: flex; }
            .sidebar { 
                transform: translateX(-100%); 
                width: 100% !important; 
                z-index: 1090; 
            }
            .sidebar.show { transform: translateX(0); }
            main { margin-left: 0 !important; padding: 80px 16px 24px 16px; }
            
            .sidebar .sidebar-brand,
            .sidebar .nav-link-text,
            .sidebar .user-menu-toggle span { 
                opacity: 1 !important; 
                pointer-events: auto !important;
            }

            .sidebar-header {
                justify-content: flex-start;
                padding: 16px 20px;
            }

            .nav-link {
                padding: 18px 30px;
                font-size: 16px;
            }

            .nav-backdrop { display: none; }
        }
    </style>
</head>

<body>
    <div id="app">
        <div class="nav-backdrop" id="navBackdrop">
            <div class="center-brand-display">
                <div class="backdrop-logo">
                    <img src="{{ $logoUrl }}" alt="Logo">
                </div>
                <div class="backdrop-tagline">Attendance Made Easier</div>
            </div>
        </div>

        <button class="mobile-toggle-btn" id="mobileToggleBtn"><i class="fas fa-bars"></i></button>

        <aside class="sidebar collapsed" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><img src="{{ $logoUrl }}" alt="Logo"></div>
                <div class="sidebar-brand">{{ $brand }}</div>
            </div>

            <ul class="sidebar-nav">
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="nav-link-text">Login</span>
                        </a>
                    </li>
                @else
                    {{-- Notice: The Absensi route has been removed because it is now merged --}}
                    @foreach([
                        ['route' => 'home', 'icon' => 'home', 'label' => 'Home', 'permission' => null],
                        ['route' => 'attendances.index', 'icon' => 'clipboard-user', 'label' => 'Attendance', 'permission' => 'attendance'],
                        ['route' => 'users.index', 'icon' => 'users', 'label' => 'Pekerja', 'permission' => 'user'],
                        ['route' => 'roles.index', 'icon' => 'shield-alt', 'label' => 'Roles', 'permission' => 'role'],
                        ['route' => 'settings.index', 'icon' => 'cog', 'label' => 'Settings', 'permission' => 'setting'],
                    ] as $item)
                        @if(!$item['permission'] || auth()->user()->canAny((array)$item['permission']))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                    <i class="fas fa-{{ $item['icon'] }}"></i>
                                    <span class="nav-link-text">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endguest
            </ul>

            @auth
            <div class="sidebar-footer">
                <ul class="user-dropdown" id="userDropdown">
                    <li>
                        <button class="dropdown-item" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </li>
                </ul>
                <button class="user-menu-toggle" id="userMenuToggle">
                    <i class="fas fa-user-circle" style="font-size: 22px;"></i>
                    <span>{{ Auth::user()->name }}</span>
                    <i class="fas fa-chevron-up" style="margin-left: auto; font-size: 11px;"></i>
                </button>
            </div>
            @endauth
        </aside>

        <main>
            <div class="container-fluid">
                {{-- Global Error Handling Message Box --}}
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%); border-left: 4px solid #ef4444; color: #991b1b; border-radius: 10px; margin-bottom: 24px;">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('userMenuToggle');
            const drop = document.getElementById('userDropdown');
            const mobile = document.getElementById('mobileToggleBtn');
            const backdrop = document.getElementById('navBackdrop');

            if (toggle) {
                toggle.onclick = (e) => { e.stopPropagation(); drop.classList.toggle('show'); };
            }

            sidebar.onmouseenter = () => { 
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('collapsed');
                    backdrop.classList.add('active');
                }
            };
            
            sidebar.onmouseleave = () => {
                if (window.innerWidth > 768) {
                    sidebar.classList.add('collapsed');
                    backdrop.classList.remove('active');
                    drop?.classList.remove('show');
                }
            };

            mobile.onclick = (e) => { 
                e.stopPropagation(); 
                sidebar.classList.toggle('show'); 
                const icon = mobile.querySelector('i');
                if (sidebar.classList.contains('show')) {
                    icon.classList.replace('fa-bars', 'fa-times');
                    mobile.style.background = '#ef4444';
                } else {
                    icon.classList.replace('fa-times', 'fa-bars');
                    mobile.style.background = '#1E3A8A';
                }
            };

            document.onclick = (e) => {
                if (drop && !drop.contains(e.target)) drop.classList.remove('show');
                if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !mobile.contains(e.target)) {
                    sidebar.classList.remove('show');
                    mobile.querySelector('i').classList.replace('fa-times', 'fa-bars');
                    mobile.style.background = '#1E3A8A';
                }
            };
        });
    </script>
</body>
</html>