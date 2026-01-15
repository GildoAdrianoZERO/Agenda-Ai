<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaAí - Gestão Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#F59E0B',
                        dark: '#111111',
                        card: '#1F1F1F'
                    }
                }
            }
        }
    </script>
    <style>body { background-color: #111; color: white; font-family: sans-serif; }</style>
</head>
<body class="bg-dark text-white min-h-screen flex flex-col">

    <nav class="border-b border-gray-800 py-4 bg-card">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="text-2xl">✂️</span>
                <span class="text-xl font-bold text-gold tracking-wide">AgendaAí</span>
            </div>
            <div>
                <a href="#" class="text-gray-300 hover:text-white transition text-sm">Login Profissional</a>
            </div>
        </div>
    </nav>
    
    <main class="flex-grow">