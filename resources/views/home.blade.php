@extends('layouts.public')

@section('content')
    <main class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-surface px-4 py-16 font-sans text-neutral-200 sm:px-6 lg:px-8">

        <div class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center">
            <div class="home-grid absolute inset-0"></div>
        </div>

        <div class="relative z-10 mx-auto mt-10 flex w-full max-w-5xl flex-col items-center">

            <a
                href="https://brayanalmengordev.netlify.app/"
                target="_blank"
                rel="noopener noreferrer"
                class="group mb-8 flex items-center gap-2 rounded-full border border-neutral-800 bg-neutral-900/50 px-4 py-1.5 text-xs font-medium text-neutral-400 backdrop-blur-md transition-all hover:border-amber-500/50 hover:text-amber-400 hover:shadow-lg hover:shadow-amber-500/20"
            >
                Desarrollado por Brayan Almengor
                <svg class="h-3.5 w-3.5 opacity-70 transition-all group-hover:translate-x-px group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>

            <div class="mb-4 flex animate-fade-in-up items-center justify-center">
                <span class="rounded-full bg-amber-500/10 px-4 py-1 text-sm font-bold uppercase tracking-widest text-amber-400 shadow-lg shadow-amber-500/20 ring-1 ring-inset ring-amber-500/20">
                    {{ config('app.brand') }}
                </span>
            </div>

            <h1 class="mb-6 text-center text-4xl font-extrabold tracking-tight text-white md:text-6xl lg:text-7xl">
                Sistema de Seguimiento
                <br class="hidden md:block" />
                <span class="bg-gradient-to-r from-amber-200 via-amber-400 to-amber-600 bg-clip-text text-transparent">
                    Financiero
                </span>
            </h1>

            <p class="mb-10 max-w-2xl text-center text-lg leading-relaxed text-neutral-400 md:text-xl">
                {{ config('seo.description', 'Centraliza tu control de presupuestos, almacena tus facturas y gestiona cotizaciones en una arquitectura moderna y robusta.') }}
            </p>

            <div class="mb-24 flex w-full flex-col items-center justify-center gap-4 sm:w-auto sm:flex-row sm:flex-wrap">
                <a
                    href="{{ url('/admin/login') }}"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-8 py-3.5 text-sm font-bold text-neutral-950 transition-all hover:-translate-y-0.5 hover:bg-amber-400 hover:shadow-xl hover:shadow-amber-500/30 sm:w-auto"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    Iniciar sesión
                </a>

                <x-pwa-install-guide />

                <a
                    href="https://github.com/brayanalmengor04"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-700 bg-neutral-900/30 px-8 py-3.5 text-sm font-semibold text-neutral-300 backdrop-blur-md transition-all hover:border-neutral-500 hover:bg-neutral-800 hover:text-white sm:w-auto"
                >
                    <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                    </svg>
                    Colaborar en GitHub
                </a>
            </div>
        </div>
    </main>
@endsection