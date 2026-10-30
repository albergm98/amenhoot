<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Amenhoot" />
    <PantallaAmenhoot>
        <div class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 px-4 py-8">
            <div class="panel-cosmo space-y-4 text-center">
                <p class="text-slate-300">Quiz en vivo con PIN</p>
                <Link href="/unirse" class="btn-cosmo w-full justify-center py-3">Unirme con PIN</Link>
                <Link href="/cuestionarios" class="btn-ghost w-full justify-center py-3">Soy anfitrión</Link>
                <Link href="/" class="block text-sm text-slate-400 underline-offset-2 hover:underline">
                    Volver a elegir juego
                </Link>
            </div>
        </div>
    </PantallaAmenhoot>
</template>
