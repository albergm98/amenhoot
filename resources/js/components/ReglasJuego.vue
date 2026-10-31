<script setup lang="ts">
import {
    cierreNormasAmenhoot,
    reglasAmenhoot,
    tituloNormasAmenhoot,
} from '@/datos/normasAmenhoot'
</script>

<template>
    <div class="panel-cosmo w-full space-y-5">
        <h2 class="text-center text-2xl tracking-wide text-slate-50">
            {{ tituloNormasAmenhoot }}
        </h2>

        <ol class="flex flex-col gap-3">
            <li
                v-for="(regla, indice) in reglasAmenhoot"
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

        <p class="text-center text-sm text-slate-400">
            {{ cierreNormasAmenhoot }}
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
