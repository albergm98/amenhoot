<script setup lang="ts">
import PantallaAmenhoot from '@/components/PantallaAmenhoot.vue'
import { Head, Link } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Stellar Rangers" />
    <PantallaAmenhoot :marca-arriba="false" fondo="/assets/img/fondo-portada.jpg">
        <div class="portada">
            <p class="elige">Elige un juego</p>
            <div class="panel-cosmo flex w-full max-w-md flex-col gap-3">
                <Link href="/amenhoot" class="btn-cosmo w-full justify-center py-4 text-xl">Amenhoot</Link>
                <Link href="/bingo" class="btn-ghost w-full justify-center py-4 text-xl">Bingo</Link>
            </div>
        </div>
    </PantallaAmenhoot>
</template>

<style scoped>
.portada {
    display: flex;
    min-height: 100dvh;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    padding: 2rem 1rem;
}

.elige {
    margin: 0;
    text-align: center;
    font-family: var(--font-display);
    font-size: clamp(1.35rem, 5vw, 1.75rem);
    letter-spacing: 0.04em;
    color: #e2e8f0;
    text-shadow: 0 4px 18px rgb(0 0 0 / 0.45);
}
</style>
