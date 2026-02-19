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

function getTextColor($hexColor) {
    $hex = str_replace('#', '', $hexColor);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.5 ? '#111827' : '#f8f9fa';
}

function hexToRgba($hex, $alpha) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "rgba($r, $g, $b, $alpha)";
}

function getCardBgColor($bgColor) {
    $hex = str_replace('#', '', $bgColor);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.5 ? '#f3f4f6' : '#ffffff';
}

function getDropdownBgColor($bgColor) {
    $hex = str_replace('#', '', $bgColor);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.5 ? '#e9ecef' : '#212529';
}

$bgColor              = '#f8f9fa';
$navBgColor           = '#0d3b66';
$bgTextColor          = getTextColor($bgColor);
$navTextColor         = '#ffffff';
$cardBgColor          = getCardBgColor($bgColor);
$cardTextColor        = getTextColor($cardBgColor);
$dropdownBgColor      = getDropdownBgColor($bgColor);
$dropdownTextColor    = getTextColor($dropdownBgColor);
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
        :root {
            --bg: {{ $bgColor }};
            --text: {{ $bgTextColor }};
            --surface: {{ $navBgColor }};
            --nav-text: #ffffff;
            --card-surface: {{ $cardBgColor }};
            --card-text: {{ $cardTextColor }};
            --dropdown-surface: {{ $dropdownBgColor }};
            --dropdown-text: {{ $dropdownTextColor }};
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: '{{ $font }}', system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background-color: var(--surface);
            color: var(--nav-text);
            z-index: 1030;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-header img {
            height: 40px;
            width: 40px;
            border-radius: 4px;
        }

        .sidebar-brand {
            font-size: 16px;
            font-weight: 700;
            color: var(--nav-text);
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-nav .nav-item {
            margin: 0;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: var(--nav-text);
            text-decoration: none;
            font-size: 15px;
            transition: background-color 0.2s ease-in-out;
        }

        .sidebar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            border-left: 3px solid #ef4444;
            padding-left: 17px;
        }

        .sidebar-nav .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-menu {
            position: relative;
        }

        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background-color: rgba(255,255,255,0.1);
            border: none;
            border-radius: 4px;
            color: var(--nav-text);
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            transition: background-color 0.2s ease-in-out;
        }

        .user-menu-toggle:hover {
            background-color: rgba(255,255,255,0.15);
        }

        .user-menu-toggle i {
            width: 18px;
        }

        .user-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background-color: var(--surface);
            border: 1px solid rgba(255,255,255,0.1);
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            list-style: none;
            margin: 0;
            padding: 8px 0;
            display: none;
            flex-direction: column;
            z-index: 100;
        }

        .user-dropdown.show {
            display: flex;
        }

        .user-dropdown .dropdown-item {
            padding: 10px 16px;
            color: var(--nav-text);
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s ease-in-out;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border: none;
            text-align: left;
            width: 100%;
            background: none;
        }

        .user-dropdown .dropdown-item:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .user-dropdown .dropdown-divider {
            margin: 6px 0;
            border: 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-dropdown .dropdown-item i {
            width: 16px;
        }

        main {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transition: width 0.3s ease-in-out;
            }

            .sidebar.show {
                width: 280px;
            }

            main {
                margin-left: 0;
            }

            .sidebar-toggle {
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1025;
                background-color: var(--surface);
                border: none;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 18px;
                display: block;
            }

            main {
                padding-top: 60px;
            }
        }

        @media (min-width: 769px) {
            .sidebar-toggle {
                display: none;
            }
        }

        .card {
            background-color: var(--card-surface) !important;
            color: var(--card-text) !important;
        }

        .card-header {
            background-color: var(--card-surface) !important;
            border-bottom: 1px solid var(--border) !important;
        }

        #loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background-color: #3498db;
            transition: width 0.3s ease-in-out, opacity 0.5s ease-in-out;
            z-index: 1031;
        }
    </style>
</head>

<body>
    <div id="app">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="{{ $logoUrl }}" alt="Logo">
                <div class="sidebar-brand">{{ $brand }}</div>
            </div>

            <ul class="sidebar-nav">
                @guest
                    @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    @endif
                @else
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('absences.*') ? 'active' : '' }}" href="{{ route('absences.index') }}">
                            <i class="fas fa-file-alt"></i> Absensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i> Pekerja
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
                    </button>
                    <ul class="user-dropdown" id="userDropdown">
                        <li>
                            <a class="dropdown-item" href="{{ route('settings.index') }}">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        @can('manage roles')
                        <li>
                            <a class="dropdown-item" href="{{ route('roles.index') }}">
                                <i class="fas fa-shield-alt"></i> Roles
                            </a>
                        </li>
                        @endcan
                        <li>
                            <a class="dropdown-item" href="{{ route('attendances.index') }}">
                                <i class="fas fa-users-check"></i> Attendance
                            </a>
                        </li>
                        <li class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                                onclick="event.preventDefault();document.getElementById('logout-form').submit();">
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
        const loadingBar = document.getElementById('loading-bar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdown = document.getElementById('userDropdown');

        document.addEventListener('DOMContentLoaded', function(){
            loadingBar.style.width = '90%';
        });

        window.addEventListener('load', function(){
            loadingBar.style.width = '100%';
            loadingBar.style.opacity = '0';
        });

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(){
                sidebar.classList.toggle('show');
            });
        }

        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', function(e){
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
        }

        document.addEventListener('click', function(e){
            if (userDropdown && !userDropdown.contains(e.target) && !userMenuToggle.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
