<script setup lang="ts">
defineProps<{
    cuentaAtras?: number | null
}>()

const reglas = [
    {
        titulo: 'Lee y elige',
        texto: 'Lee la pregunta y pulsa una de las 4 opciones.',
    },
    {
        titulo: 'Más rápido, más puntos',
        texto: 'Si aciertas, cuanto antes respondas, más puntos sumas.',
    },
    {
        titulo: 'Si fallas, cero',
        texto: 'No sumas puntos y se corta tu racha.',
    },
    {
        titulo: 'Solución y ranking',
        texto: 'Cuando todos respondan o acabe el tiempo, veréis la respuesta y la clasificación.',
    },
    {
        titulo: 'Podio final',
        texto: 'Al terminar se revela el 3º, el 2º y el campeón.',
    },
]
</script>

<template>
    <div class="panel-cosmo w-full space-y-5">
        <h2 class="text-center text-2xl tracking-wide text-slate-50">
            Reglas del juego
        </h2>

        <ol class="flex flex-col gap-3">
            <li
                v-for="(regla, indice) in reglas"
                :key="indice"
                class="fila-regla flex items-start gap-4 rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3.5"
                :style="{ animationDelay: `${indice * 70}ms` }"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-base text-slate-900"
                    aria-hidden="true"
                >
                    {{ indice + 1 }}
                </span>
                <div class="min-w-0 flex-1 space-y-0.5 pt-0.5">
                    <p class="text-base leading-snug text-slate-50">{{ regla.titulo }}</p>
                    <p class="text-sm leading-relaxed text-slate-300">{{ regla.texto }}</p>
                </div>
            </li>
        </ol>

        <p
            v-if="cuentaAtras != null && cuentaAtras > 0"
            class="rounded-2xl border border-accent/30 bg-accent/10 py-3 text-center text-2xl text-slate-50"
            aria-live="polite"
        >
            Empieza en {{ cuentaAtras }}…
        </p>
        <p v-else class="text-center text-sm text-slate-400">
            Cuando el anfitrión pulse Empezar, tendréis 5 segundos para repasarlo.
        </p>
    </div>
</template>

<style scoped>
.fila-regla {
    animation: entrar-regla 0.45s ease both;
}

@keyframes entrar-regla {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .fila-regla {
        animation: none;
    }
}
</style>
