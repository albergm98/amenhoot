<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    clave: '',
})

const entrar = () => form.post('/anfitrion/acceso')
</script>

<template>
    <Head title="Acceso anfitrión — Amenhoot" />
    <PantallaAmenhoot>
        <div class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 px-4 py-8">
            <form class="panel-cosmo space-y-4 text-center" @submit.prevent="entrar">
                <p class="font-display text-2xl font-bold tracking-tight text-slate-50">Anfitrión</p>
                <p class="text-sm text-slate-300">Introduce la contraseña para gestionar Amenhoot</p>
                <label class="block text-left text-sm text-slate-300" for="clave-anfitrion">Contraseña</label>
                <input
                    id="clave-anfitrion"
                    v-model="form.clave"
                    class="w-full rounded-xl border border-white/15 bg-slate-950/50 px-4 py-3 text-center text-lg tracking-[0.3em] text-slate-50 outline-none focus:border-sky-400"
                    type="password"
                    inputmode="numeric"
                    autocomplete="current-password"
                    maxlength="16"
                    required
                    aria-label="Contraseña de anfitrión"
                />
                <p v-if="form.errors.clave" class="text-sm text-ko">{{ form.errors.clave }}</p>
                <button class="btn-cosmo w-full justify-center py-3.5 text-lg" type="submit" :disabled="form.processing">
                    Entrar
                </button>
                <Link href="/amenhoot" class="block text-sm text-slate-400 underline-offset-2 hover:underline">
                    Volver
                </Link>
            </form>
        </div>
    </PantallaAmenhoot>
</template>
