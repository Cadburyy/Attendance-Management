<!doctype html>
@php
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

$settings = cache()->remember('app_settings', 60, function () {
    return Setting::pluck('value', 'key')->toArray();
});

$brand      = $settings['brand_name'] ?? 'Company';
$font       = $settings['font'] ?? 'Nunito';

$logoPath = $settings['logo_path'] ?? '';
$logoPath = str_replace('\\', '/', $logoPath); 

$logoUrl = !empty($logoPath)
    ? asset('storage/'.$logoPath)
    : asset('images/logo.png');

$faviconPath = $settings['favicon_path'] ?? '';
$faviconPath = str_replace('\\', '/', $faviconPath);

$faviconUrl = !empty($faviconPath)
    ? asset('storage/'.$faviconPath)
    : asset('favicon.ico');

$fontHrefName = str_replace(' ', '+', $font);
@endphp

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $brand }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family={{ $fontHrefName }}:400,600,700" rel="stylesheet">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: '{{ $font }}', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fb;
            color: #2d3748;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            /* Gradient from Left (Darker) to Right (Lighter) */
            background: linear-gradient(90deg, #172554 0%, #1E3A8A 100%);
            color: white;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 260px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 16px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            min-height: 70px;
            white-space: nowrap;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .sidebar-logo {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden; 
        }

        .sidebar-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transform: scale(1.2); 
        }

        .sidebar-brand {
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
            letter-spacing: -0.5px;
            transition: opacity 0.2s ease;
            opacity: 1;
        }

        .sidebar.collapsed .sidebar-brand {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 28px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.25s ease;
            position: relative;
            white-space: nowrap;
        }

        .sidebar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-nav .nav-link.active,
        .sidebar.collapsed .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-weight: 600;
            border-left: 4px solid #60A5FA;
            padding-left: 24px; /* Offset the 4px border perfectly */
        }

        .sidebar-nav .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .nav-link-text {
            white-space: nowrap;
            transition: opacity 0.2s ease;
            opacity: 1;
        }

        .sidebar.collapsed .nav-link-text {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            white-space: nowrap;
        }

        .user-menu {
            position: relative;
        }

        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .user-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-menu-toggle i:first-child {
            font-size: 20px;
            flex-shrink: 0;
        }

        .user-menu-toggle span,
        .user-menu-toggle i:last-child {
            transition: opacity 0.2s ease;
            opacity: 1;
        }

        .user-menu-toggle i:last-child {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.3s ease, opacity 0.2s ease;
        }

        .user-menu-toggle.open i:last-child {
            transform: rotate(180deg);
        }

        .sidebar.collapsed .user-menu-toggle span,
        .sidebar.collapsed .user-menu-toggle i:last-child {
            opacity: 0;
            pointer-events: none;
        }

        .user-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 8px;
            list-style: none;
            margin: 0 0 8px 0;
            padding: 6px 0;
            display: none;
            flex-direction: column;
            z-index: 1001;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .user-dropdown.show {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }

        .user-dropdown .dropdown-item {
            padding: 10px 16px;
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
        }

        .user-dropdown .dropdown-item:hover {
            background: #fef2f2;
        }

        main {
            margin-left: 80px;
            padding: 32px 24px;
            min-height: 100vh;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        main .container-fluid {
            max-width: 100%;
            padding: 0;
        }

        .mobile-toggle-btn {
            display: none;
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1050;
            background: #1E3A8A;
            color: white;
            border: none;
            border-radius: 8px;
            width: 44px;
            height: 44px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .mobile-toggle-btn {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 260px !important;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar.collapsed {
                width: 260px;
            }
            
            .sidebar.collapsed .sidebar-brand,
            .sidebar.collapsed .nav-link-text,
            .sidebar.collapsed .user-menu-toggle span,
            .sidebar.collapsed .user-menu-toggle i:last-child {
                opacity: 1;
                pointer-events: auto;
            }

            main {
                margin-left: 0 !important;
                padding: 70px 16px 24px 16px;
            }
        }

        #loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: #60A5FA;
            transition: width 0.3s ease-in-out, opacity 0.5s ease-in-out;
            z-index: 1060;
            box-shadow: 0 0 10px rgba(96, 165, 250, 0.8);
        }
    </style>
</head>

<body>
    <div id="app">
        <button class="mobile-toggle-btn" id="mobileToggleBtn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <aside class="sidebar collapsed" id="sidebar">
            <div class="sidebar-header">
                <div class="header-content">
                    <div class="sidebar-logo">
                        <img src="{{ $logoUrl }}" alt="Logo">
                    </div>
                    <div class="sidebar-brand">
                        {{ $brand }}
                    </div>
                </div>
            </div>

            <ul class="sidebar-nav">
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt"></i>
                                <span class="nav-link-text">Login</span>
                            </a>
                        </li>
                    @endif
                @else
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="fas fa-home"></i>
                            <span class="nav-link-text">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('absences.*') ? 'active' : '' }}" href="{{ route('absences.index') }}">
                            <i class="fas fa-file-alt"></i>
                            <span class="nav-link-text">Absensi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('attendances.*') ? 'active' : '' }}" href="{{ route('attendances.index') }}">
                            <i class="fas fa-clipboard-user"></i>
                            <span class="nav-link-text">Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i>
                            <span class="nav-link-text">Pekerja</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                            <i class="fas fa-shield-alt"></i>
                            <span class="nav-link-text">Roles</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                            <i class="fas fa-cog"></i>
                            <span class="nav-link-text">Settings</span>
                        </a>
                    </li>
                @endguest
            </ul>

            @auth
            <div class="sidebar-footer">
                <div class="user-menu">
                    <button class="user-menu-toggle" id="userMenuToggle">
                        <i class="fas fa-user-circle"></i>
                        <span>{{ Auth::user()->name }}</span>
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <ul class="user-dropdown" id="userDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            @endauth
        </aside>

        <main>
            <div class="container-fluid">
                @yield('content')
            </div>
        </main>
    </div>

    <div id="loading-bar"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadingBar = document.getElementById('loading-bar');
            const sidebar = document.getElementById('sidebar');
            const userMenuToggle = document.getElementById('userMenuToggle');
            const userDropdown = document.getElementById('userDropdown');
            const mobileToggleBtn = document.getElementById('mobileToggleBtn');

            loadingBar.style.width = '90%';
            window.addEventListener('load', function() {
                loadingBar.style.width = '100%';
                setTimeout(() => loadingBar.style.opacity = '0', 300);
            });

            if (userMenuToggle) {
                userMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenuToggle.classList.toggle('open');
                    userDropdown.classList.toggle('show');
                });
            }

            document.addEventListener('click', function(e) {
                if (userDropdown && !userDropdown.contains(e.target) && !userMenuToggle.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    userMenuToggle.classList.remove('open');
                }
            });

            sidebar.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('collapsed');
                }
            });

            sidebar.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.add('collapsed');
                    if(userDropdown) userDropdown.classList.remove('show');
                    if(userMenuToggle) userMenuToggle.classList.remove('open');
                }
            });

            if (mobileToggleBtn) {
                mobileToggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                });
            }

            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(e.target) && !mobileToggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>
</html>