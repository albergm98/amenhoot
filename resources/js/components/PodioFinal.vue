<script setup lang="ts">
import confetti from 'canvas-confetti'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

type EntradaPodio = {
    id: number
    apodo: string
    puntuacion: number
    racha?: number
    equipo?: string | null
}

type EntradaEquipo = {
    id: number
    nombre: string
    color: string
    puntuacion: number
}

const props = withDefaults(
    defineProps<{
        podio: EntradaPodio[]
        podioEquipos?: EntradaEquipo[] | null
        confeti?: boolean
        instantaneo?: boolean
        titulo?: string
    }>(),
    {
        confeti: true,
        instantaneo: false,
        titulo: 'Clasificación final',
    },
)

type FasePodio = 'espera' | 'tercero' | 'segundo' | 'primero' | 'lista'

const fase = ref<FasePodio>('espera')
const filasVisibles = ref(0)
const tiempos: number[] = []

const ranking = computed(() =>
    [...props.podio].sort((a, b) => b.puntuacion - a.puntuacion || a.id - b.id),
)

const primero = computed(() => ranking.value[0] ?? null)
const segundo = computed(() => ranking.value[1] ?? null)
const tercero = computed(() => ranking.value[2] ?? null)

const programar = (ms: number, fn: () => void) => {
    tiempos.push(window.setTimeout(fn, ms))
}

const lanzarConfeti = (fuerte = false) => {
    if (!props.confeti) {
        return
    }
    confetti({
        particleCount: fuerte ? 180 : 90,
        spread: fuerte ? 90 : 65,
        origin: { y: 0.7 },
        colors: ['#e2e8f0', '#94a3b8', '#f8fafc', '#34d399', '#fbbf24'],
    })
}

const mostrarTodo = () => {
    fase.value = 'lista'
    filasVisibles.value = ranking.value.length
}

onMounted(() => {
    if (props.instantaneo || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        mostrarTodo()
        if (!props.instantaneo) {
            lanzarConfeti(true)
        }
        return
    }

    programar(600, () => {
        if (tercero.value) {
            fase.value = 'tercero'
            lanzarConfeti(false)
        } else if (segundo.value) {
            fase.value = 'segundo'
            lanzarConfeti(false)
        } else {
            fase.value = 'primero'
            lanzarConfeti(true)
        }
    })

    if (tercero.value) {
        programar(2200, () => {
            fase.value = 'segundo'
            lanzarConfeti(false)
        })
        programar(4000, () => {
            fase.value = 'primero'
            lanzarConfeti(true)
        })
        programar(6200, () => {
            fase.value = 'lista'
        })
    } else if (segundo.value) {
        programar(2200, () => {
            fase.value = 'primero'
            lanzarConfeti(true)
        })
        programar(4400, () => {
            fase.value = 'lista'
        })
    } else {
        programar(600, () => {
            fase.value = 'primero'
            lanzarConfeti(true)
        })
        programar(2800, () => {
            fase.value = 'lista'
        })
    }
})

onUnmounted(() => {
    tiempos.forEach((t) => window.clearTimeout(t))
})

const mostrarBarra = (puesto: 1 | 2 | 3) => {
    if (fase.value === 'lista') {
        return true
    }
    if (puesto === 3) {
        return ['tercero', 'segundo', 'primero', 'lista'].includes(fase.value)
    }
    if (puesto === 2) {
        return ['segundo', 'primero', 'lista'].includes(fase.value)
    }
    return ['primero', 'lista'].includes(fase.value)
}

const onListaLista = () => {
    if (filasVisibles.value > 0) {
        return
    }
    ranking.value.forEach((_, indice) => {
        programar(180 * indice, () => {
            filasVisibles.value = indice + 1
        })
    })
}

watch(fase, (nueva) => {
    if (nueva === 'lista') {
        onListaLista()
    }
})

watch(
    () => props.podio,
    () => {
        if (props.instantaneo) {
            mostrarTodo()
        }
    },
)

const faseEsLista = computed(() => fase.value === 'lista')
</script>

<template>
    <section class="podio mx-auto flex w-full max-w-3xl flex-col items-center gap-6 py-2" aria-label="Podio">
        <header class="text-center">
            <h2 class="titulo-cosmo text-slate-100">{{ titulo }}</h2>
            <p v-if="!instantaneo" class="mt-2 text-slate-300">
                <span v-if="fase === 'espera'">Preparando el podio…</span>
                <span v-else-if="fase === 'tercero'">¡Bronce!</span>
                <span v-else-if="fase === 'segundo'">¡Plata!</span>
                <span v-else-if="fase === 'primero'">¡Campeón!</span>
                <span v-else>Ranking completo</span>
            </p>
        </header>

        <div class="barras flex w-full items-end justify-center gap-3 md:gap-5" aria-live="polite">
            <div
                v-if="segundo"
                class="barra"
                :class="{ visible: mostrarBarra(2) }"
            >
                <p class="apodo">{{ segundo.apodo }}</p>
                <p class="pts">{{ segundo.puntuacion }} pts</p>
                <div class="columna plata">2</div>
            </div>

            <div
                v-if="primero"
                class="barra"
                :class="{ visible: mostrarBarra(1), campeon: fase === 'primero' || fase === 'lista' }"
            >
                <p class="apodo">{{ primero.apodo }}</p>
                <p class="pts">{{ primero.puntuacion }} pts</p>
                <div class="columna oro">1</div>
            </div>

            <div
                v-if="tercero"
                class="barra"
                :class="{ visible: mostrarBarra(3) }"
            >
                <p class="apodo">{{ tercero.apodo }}</p>
                <p class="pts">{{ tercero.puntuacion }} pts</p>
                <div class="columna bronce">3</div>
            </div>
        </div>

        <div
            v-if="faseEsLista"
            class="panel-cosmo w-full max-w-lg space-y-2"
        >
            <h3 class="mb-3 text-center text-lg text-slate-100">Todos los jugadores</h3>
            <ol class="space-y-2">
                <li
                    v-for="(jugador, indice) in ranking"
                    :key="jugador.id"
                    class="fila-ranking"
                    :class="{ visible: indice < filasVisibles }"
                >
                    <span class="puesto">{{ indice + 1 }}</span>
                    <span class="nombre">
                        {{ jugador.apodo }}
                        <span v-if="jugador.equipo" class="equipo">· {{ jugador.equipo }}</span>
                    </span>
                    <span class="puntos">{{ jugador.puntuacion }}</span>
                </li>
            </ol>
        </div>

        <div v-if="faseEsLista && podioEquipos?.length" class="panel-cosmo w-full max-w-lg space-y-3">
            <h3 class="text-center text-lg text-slate-100">Equipos</h3>
            <div
                v-for="(equipo, indice) in podioEquipos"
                :key="equipo.id"
                class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3"
            >
                <span class="flex items-center gap-3 text-slate-200">
                    <span class="h-3 w-3 rounded-full" :style="{ background: equipo.color }" />
                    <span>{{ indice + 1 }}. {{ equipo.nombre }}</span>
                </span>
                <span class="text-slate-100">{{ equipo.puntuacion }}</span>
            </div>
        </div>
    </section>
</template>

<style scoped>
.barras {
    min-height: 12rem;
}

.barra {
    display: flex;
    width: 5.5rem;
    flex-direction: column;
    align-items: center;
    gap: 0.45rem;
    opacity: 0;
    transform: translateY(2.5rem) scale(0.9);
    transition: opacity 0.55s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}

@media (min-width: 768px) {
    .barra {
        width: 7.5rem;
    }

    .barras {
        min-height: 14rem;
    }
}

.barra.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.barra.campeon {
    animation: pulso-campeon 0.9s ease;
}

.apodo {
    margin: 0;
    max-width: 100%;
    overflow: hidden;
    font-size: 0.95rem;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #f8fafc;
}

.pts {
    margin: 0;
    font-size: 0.8rem;
    color: #cbd5e1;
}

.columna {
    display: flex;
    width: 100%;
    align-items: flex-start;
    justify-content: center;
    border-radius: 1.25rem 1.25rem 0 0;
    padding-top: 0.85rem;
    font-size: 1.75rem;
    color: white;
}

.oro {
    height: 11rem;
    background: linear-gradient(180deg, rgb(251 191 36 / 0.85), rgb(15 23 42 / 0.95));
}

.plata {
    height: 8rem;
    background: linear-gradient(180deg, rgb(203 213 225 / 0.75), rgb(15 23 42 / 0.95));
}

.bronce {
    height: 6rem;
    background: linear-gradient(180deg, rgb(180 128 80 / 0.8), rgb(15 23 42 / 0.95));
}

.fila-ranking {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 1rem;
    background: rgb(255 255 255 / 0.05);
    padding: 0.7rem 1rem;
    opacity: 0;
    transform: translateX(-0.75rem);
    transition: opacity 0.35s ease, transform 0.35s ease;
}

.fila-ranking.visible {
    opacity: 1;
    transform: translateX(0);
}

.puesto {
    display: grid;
    width: 1.75rem;
    place-items: center;
    color: #94a3b8;
}

.nombre {
    flex: 1;
    color: #f1f5f9;
}

.equipo {
    color: #94a3b8;
}

.puntos {
    color: #e2e8f0;
}

@keyframes pulso-campeon {
    0% {
        transform: scale(0.92);
    }
    50% {
        transform: scale(1.06);
    }
    100% {
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .barra,
    .fila-ranking {
        opacity: 1;
        transform: none;
        transition: none;
        animation: none;
    }
}
</style>
