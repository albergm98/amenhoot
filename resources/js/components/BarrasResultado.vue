<script setup lang="ts">
import { computed } from 'vue'

type Barra = {
    opcion_id: number
    texto: string
    total: number
    es_correcta?: boolean
}

const props = defineProps<{
    barras: Barra[]
}>()

const maximo = computed(() => Math.max(1, ...props.barras.map((barra) => barra.total)))
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            v-for="barra in barras"
            :key="barra.opcion_id"
            class="rounded-2xl px-3 py-2.5 transition"
            :class="barra.es_correcta
                ? 'borde-correcta bg-ok/15 ring-2 ring-ok/70'
                : 'border border-white/10 bg-white/[0.04] opacity-70'"
        >
            <div class="mb-1.5 flex items-center justify-between gap-2 text-sm">
                <span
                    class="flex min-w-0 items-center gap-2"
                    :class="barra.es_correcta ? 'text-ok' : 'text-slate-200'"
                >
                    <span
                        v-if="barra.es_correcta"
                        class="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-ok text-sm text-cosmo-950"
                        aria-hidden="true"
                    >
                        ✓
                    </span>
                    <span class="truncate font-medium">{{ barra.texto }}</span>
                    <span
                        v-if="barra.es_correcta"
                        class="shrink-0 rounded-full bg-ok/25 px-2 py-0.5 text-xs uppercase tracking-wide text-ok"
                    >
                        Correcta
                    </span>
                </span>
                <span
                    class="shrink-0 text-base"
                    :class="barra.es_correcta ? 'text-ok' : 'text-slate-300'"
                >
                    {{ barra.total }}
                </span>
            </div>
            <div
                class="h-3 overflow-hidden rounded-full"
                :class="barra.es_correcta ? 'bg-ok/20' : 'bg-slate-800/80'"
            >
                <div
                    class="h-full rounded-full transition-all duration-700"
                    :class="barra.es_correcta ? 'bg-ok shadow-[0_0_12px_rgb(52_211_153_/_.55)]' : 'bg-slate-400'"
                    :style="{ width: `${(barra.total / maximo) * 100}%` }"
                />
            </div>
        </div>
    </div>
</template>
