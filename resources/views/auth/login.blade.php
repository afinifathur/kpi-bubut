<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KPI Bubut</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: radial-gradient(circle at top right, #f8fafc, #f1f5f9);
            font-family: 'Inter', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-blue-600 text-white shadow-2xl shadow-blue-200 mb-6">
                <span class="material-icons text-4xl">analytics</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">KPI BUBUT</h1>
            <p class="text-slate-500 mt-2 font-medium">Monitoring System & Performance</p>
        </div>

        <div class="glass-card rounded-[2.5rem] p-10 shadow-2xl shadow-slate-200/50">
            <h2 class="text-xl font-bold text-slate-800 mb-8">Sign In</h2>

            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2 ml-1">Email Address</label>
                    <div class="relative">
                        <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">mail</span>
                        <input type="email" name="email" required 
                            class="w-full h-14 pl-12 pr-4 rounded-2xl border-slate-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-slate-900 font-medium"
                            placeholder="name@peroniks.com" value="{{ old('email') }}">
                    </div>
                    @error('email')
                        <p class="text-rose-500 text-xs mt-2 ml-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2 ml-1">Password</label>
                    <div class="relative">
                        <span class="material-icons absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                        <input type="password" name="password" required 
                            class="w-full h-14 pl-12 pr-4 rounded-2xl border-slate-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all text-slate-900 font-medium"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between ml-1">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-5 h-5 rounded-lg border-slate-300 text-blue-600 focus:ring-blue-500/20">
                        <span class="text-sm font-bold text-slate-500 group-hover:text-slate-700 transition-colors">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-bold shadow-xl shadow-blue-200 active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-8">
                    <span>Sign Into Dashboard</span>
                    <span class="material-icons text-xl">arrow_forward</span>
                </button>
            </form>
        </div>

        <p class="text-center mt-10 text-slate-400 text-sm font-medium">
            &copy; {{ date('Y') }} PT Peroniks Indonesia. Monitoring Analytics.
        </p>
    </div>
</body>
</html>
