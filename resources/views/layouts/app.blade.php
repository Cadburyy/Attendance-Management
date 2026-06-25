<!doctype html>
@php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

$settings = Cache::remember('app_settings', 60, function() {
    try {
        return DB::table('settings')->pluck('value', 'key')->toArray();
    } catch (\Exception $e) {
        return []; 
    }
});

$brand = $settings['brand_name'] ?? 'Company';
$font = $settings['font'] ?? 'Nunito';

$logoPath = isset($settings['logo_path']) && is_string($settings['logo_path']) ? $settings['logo_path'] : '';
$faviconPath = isset($settings['favicon_path']) && is_string($settings['favicon_path']) ? $settings['favicon_path'] : '';

$logoUrl = !empty($logoPath) ? asset('storage/'.str_replace('\\', '/', $logoPath)) : asset('images/logo.png');
$faviconUrl = !empty($faviconPath) ? asset('storage/'.str_replace('\\', '/', $faviconPath)) : asset('favicon.ico');
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

        /* Global Mobile Navbar (Hidden on Desktop) */
        .mobile-navbar {
            display: none;
        }

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
        
        .nav-backdrop.active { opacity: 1; padding-left: var(--sb-width); }

        .center-brand-display { text-align: center; transform: translateY(20px); transition: 0.5s ease; opacity: 0; }
        .nav-backdrop.active .center-brand-display { transform: translateY(0); opacity: 1; }

        .backdrop-logo { width: 120px; height: 120px; background: white; border-radius: 24px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 20px; display: inline-block; }
        .backdrop-logo img { width: 100%; height: 100%; object-fit: contain; }
        .backdrop-tagline { color: white; font-size: 24px; font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.3); letter-spacing: 1px; }

        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (min-width: 769px) {
            .sidebar.collapsed .user-menu-toggle span,
            .sidebar.collapsed .user-menu-toggle i:last-child { 
                opacity: 0; pointer-events: none;
            }
        }

        /* Mobile specific styling */
        @media (max-width: 768px) {
            /* Show the top navbar */
            .mobile-navbar {
                display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 65px;
                background: var(--sb-bg); z-index: 1100; align-items: center;
                justify-content: space-between; padding: 0 16px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            }
            .mobile-navbar-brand {
                display: flex; align-items: center; gap: 12px; color: white;
                font-weight: 700; font-size: 18px; text-decoration: none;
            }
            .mobile-navbar-brand img {
                width: 36px; height: 36px; border-radius: 8px; background: white;
                padding: 4px; object-fit: contain;
            }
            .mobile-toggle-btn {
                background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);
                border-radius: 8px; width: 40px; height: 40px; display: flex;
                align-items: center; justify-content: center; transition: background 0.3s;
                cursor: pointer;
            }

            /* Adjust Sidebar for Mobile */
            .sidebar { 
                transform: translateY(-120%); 
                width: 100% !important; 
                top: 65px; /* Sits exactly below the mobile navbar */
                height: calc(100vh - 65px);
                z-index: 1090; 
                opacity: 0;
            }
            .sidebar.show { 
                transform: translateY(0); 
                opacity: 1;
            }
            
            /* Hide the original sidebar header since we now have the mobile navbar */
            .sidebar-header { display: none; }

            .sidebar .sidebar-brand,
            .sidebar .nav-link-text,
            .sidebar .user-menu-toggle span { opacity: 1 !important; pointer-events: auto !important; }

            .nav-link { padding: 18px 30px; font-size: 16px; }
            .nav-backdrop { display: none; }

            /* Add padding to main content to clear the new mobile navbar */
            main { margin-left: 0 !important; padding: 85px 16px 24px 16px; }
        }
    </style>
</head>

<body>
    <div id="app">
        
        <!-- NEW Global Mobile Navbar -->
        <div class="mobile-navbar">
            <a href="{{ route('home') }}" class="mobile-navbar-brand">
                <img src="{{ $logoUrl }}" alt="Logo">
                <span>{{ $brand }}</span>
            </a>
            <button class="mobile-toggle-btn" id="mobileToggleBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="nav-backdrop" id="navBackdrop">
            <div class="center-brand-display">
                <div class="backdrop-logo">
                    <img src="{{ $logoUrl }}" alt="Logo">
                </div>
                <div class="backdrop-tagline">Attendance Made Easier</div>
            </div>
        </div>

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
                    @foreach([
                        ['route' => 'home', 'icon' => 'home', 'label' => 'Home', 'permission' => null],
                        ['route' => 'attendances.index', 'icon' => 'clipboard-user', 'label' => 'Attendance', 'permission' => 'attendance'],
                        ['route' => 'users.index', 'icon' => 'users', 'label' => 'Employee', 'permission' => 'user'],
                        ['route' => 'roles.index', 'icon' => 'shield-alt', 'label' => 'Role', 'permission' => 'role'],
                        ['route' => 'settings.index', 'icon' => 'cog', 'label' => 'Setting', 'permission' => 'setting'],
                    ] as $item)
                        @php
                            $hasPermission = !$item['permission'] || (auth()->check() && auth()->user()->canAny((array)$item['permission']));
                        @endphp
                        @if($hasPermission)
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
                    mobile.style.borderColor = '#ef4444';
                } else {
                    icon.classList.replace('fa-times', 'fa-bars');
                    mobile.style.background = 'rgba(255,255,255,0.1)';
                    mobile.style.borderColor = 'rgba(255,255,255,0.2)';
                }
            };

            document.onclick = (e) => {
                if (drop && !drop.contains(e.target)) drop.classList.remove('show');
                if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !mobile.contains(e.target)) {
                    sidebar.classList.remove('show');
                    mobile.querySelector('i').classList.replace('fa-times', 'fa-bars');
                    mobile.style.background = 'rgba(255,255,255,0.1)';
                    mobile.style.borderColor = 'rgba(255,255,255,0.2)';
                }
            };
        });
    </script>

    @auth
        @can('override')
            <!-- Chatbot Widget -->
            <div id="chatbot-fab" onclick="toggleChatbot()" style="position:fixed;bottom:30px;right:30px;width:60px;height:60px;background:linear-gradient(135deg,#1E3A8A,#3B82F6);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 8px 25px rgba(30,58,138,0.4);z-index:9999;transition:all 0.3s;border:none;">
                <i class="fas fa-comments" style="color:white;font-size:24px;"></i>
            </div>

            <div id="chatbot-window" style="display:none;position:fixed;bottom:100px;right:30px;width:380px;height:500px;background:white;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.2);z-index:9999;overflow:hidden;flex-direction:column;font-family:inherit;">
                <!-- Header -->
                <div style="background:linear-gradient(135deg,#172554,#1E3A8A);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-robot" style="color:white;font-size:20px;"></i>
                        <div>
                            <div style="color:white;font-weight:700;font-size:15px;line-height:1.2;">Asisten HR AI</div>
                            <small style="color:rgba(255,255,255,0.7);font-size:11px;">Aktif • openai/gpt-oss-20b</small>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <i class="fas fa-redo-alt" onclick="clearChatHistory()" title="Mulai Sesi Baru" style="color:rgba(255,255,255,0.7);cursor:pointer;font-size:14px;transition:color 0.2s;"></i>
                        <i class="fas fa-times" onclick="toggleChatbot()" style="color:rgba(255,255,255,0.7);cursor:pointer;font-size:16px;"></i>
                    </div>
                </div>
                
                <!-- Messages -->
                <div id="chat-messages" style="flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:12px;background:#f8fafc;">
                    <div style="background:#ffffff;border:1px solid #f1f5f9;padding:12px 16px;border-radius:14px;border-top-left-radius:4px;max-width:85%;font-size:14px;color:#334155;box-shadow:0 2px 5px rgba(0,0,0,0.02);line-height:1.5;">
                        Halo <strong>{{ Auth::user()->name }}</strong>! 👋 Saya asisten pintar absensi. Silakan tanyakan informasi rekap bulanan, status hari ini, atau jadwal shift.
                    </div>
                </div>
                
                <!-- Input Area -->
                <div style="padding:14px;border-top:1px solid #e2e8f0;display:flex;gap:10px;background:white;">
                    <input id="chat-input" type="text" placeholder="Ketik pertanyaan..." onkeypress="if(event.key==='Enter')sendChatMessage()" style="flex:1;padding:10px 16px;border:2px solid #e2e8f0;border-radius:12px;font-size:14px;outline:none;transition:border-color 0.2s;">
                    <button onclick="sendChatMessage()" style="background:linear-gradient(135deg,#1E3A8A,#3B82F6);border:none;color:white;width:42px;height:42px;border-radius:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform 0.2s;box-shadow:0 4px 10px rgba(30,58,138,0.2);">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <script>
            let chatHistory = [];

            function toggleChatbot() {
                const w = document.getElementById('chatbot-window');
                const fab = document.getElementById('chatbot-fab');
                if (w.style.display === 'none') {
                    w.style.display = 'flex';
                    fab.style.transform = 'scale(0.9) rotate(90deg)';
                } else {
                    w.style.display = 'none';
                    fab.style.transform = 'scale(1) rotate(0deg)';
                }
            }

            function clearChatHistory() {
                chatHistory = [];
                const messagesDiv = document.getElementById('chat-messages');
                messagesDiv.innerHTML = `
                    <div style="background:#ffffff;border:1px solid #f1f5f9;padding:12px 16px;border-radius:14px;border-top-left-radius:4px;max-width:85%;font-size:14px;color:#334155;box-shadow:0 2px 5px rgba(0,0,0,0.02);line-height:1.5;">
                        Halo <strong>{{ Auth::user()->name }}</strong>! Sesi baru telah dimulai. Memory chat sebelumnya telah di-reset. 👋 Ada yang bisa saya bantu?
                    </div>
                `;
            }

            function formatMarkdown(text) {
                if (!text) return '';
                // Escape HTML first
                let escaped = text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");

                // Convert **bold** to <strong>bold</strong>
                escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                
                // Convert *italic* to <em>italic</em>
                escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
                
                // Convert lines starting with "- " or "* " to bullet points
                let lines = escaped.split('\n');
                let formattedLines = lines.map(line => {
                    let trimmed = line.trim();
                    if (trimmed.startsWith('- ')) {
                        return `• ${trimmed.substring(2)}`;
                    }
                    if (trimmed.startsWith('* ')) {
                        return `• ${trimmed.substring(2)}`;
                    }
                    return line;
                });
                
                return formattedLines.join('<br>');
            }

            async function sendChatMessage() {
                const input = document.getElementById('chat-input');
                const msg = input.value.trim();
                if (!msg) return;
                input.value = '';

                const messagesDiv = document.getElementById('chat-messages');

                // User bubble
                messagesDiv.innerHTML += `<div style="background:linear-gradient(135deg,#1E3A8A,#3B82F6);color:white;padding:12px 16px;border-radius:14px;border-top-right-radius:4px;max-width:85%;align-self:flex-end;font-size:14px;box-shadow:0 4px 10px rgba(30,58,138,0.15);word-break:break-word;line-height:1.5;">${msg}</div>`;
                messagesDiv.scrollTop = messagesDiv.scrollHeight;

                // Loading indicator
                const loadingId = 'loading-' + Date.now();
                messagesDiv.innerHTML += `<div id="${loadingId}" style="background:#ffffff;border:1px solid #f1f5f9;padding:12px 16px;border-radius:14px;border-top-left-radius:4px;max-width:85%;font-size:14px;color:#94a3b8;box-shadow:0 2px 5px rgba(0,0,0,0.02);"><i class="fas fa-circle-notch fa-spin"></i> Sedang berpikir...</div>`;
                messagesDiv.scrollTop = messagesDiv.scrollHeight;

                try {
                    const res = await fetch('http://127.0.0.1:5000/chat', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            message: msg,
                            conversation_history: chatHistory,
                            user_name: '{{ Auth::user()->name }}',
                            is_admin: {{ Auth::user()->can('override') ? 'true' : 'false' }}
                        })
                    });
                    const data = await res.json();
                    
                    const loader = document.getElementById(loadingId);
                    if (loader) loader.remove();

                    const replyText = data.reply || 'Maaf, terjadi kesalahan.';
                    
                    // Push to local session memory
                    chatHistory.push({ role: 'user', content: msg });
                    chatHistory.push({ role: 'assistant', content: replyText });
                    if (chatHistory.length > 20) {
                        chatHistory = chatHistory.slice(-20);
                    }

                    // Bot reply bubble (convert Markdown format to HTML)
                    const replyHtml = formatMarkdown(replyText);
                    messagesDiv.innerHTML += `<div style="background:#ffffff;border:1px solid #f1f5f9;padding:12px 16px;border-radius:14px;border-top-left-radius:4px;max-width:85%;font-size:14px;color:#334155;box-shadow:0 2px 5px rgba(0,0,0,0.02);word-break:break-word;line-height:1.5;">${replyHtml}</div>`;
                } catch (e) {
                    const loader = document.getElementById(loadingId);
                    if (loader) loader.remove();
                    messagesDiv.innerHTML += `<div style="background:#fef2f2;padding:12px 16px;border-radius:14px;border-top-left-radius:4px;max-width:85%;font-size:14px;color:#dc2626;">Koneksi gagal. Pastikan server AI aktif.</div>`;
                }
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }
            </script>
        @endcan
    @endauth

</body>
</html>