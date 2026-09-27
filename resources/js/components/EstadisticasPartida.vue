<script setup lang="ts">
type Resumen = {
    jugadores: number
    preguntas_totales: number
    preguntas_con_respuestas: number
    respuestas: number
    aciertos: number
    fallos: number
    porcentaje_acierto: number
    tiempo_medio_ms: number | null
    puntos_repartidos: number
    duracion_segundos: number | null
}

type Destacados = {
    mas_rapido: { id: number; apodo: string; milisegundos: number } | null
    mas_preciso: { id: number; apodo: string; porcentaje: number; correctas: number } | null
    mayor_puntuacion: { id: number; apodo: string; puntuacion: number } | null
    mayor_racha: { id: number; apodo: string; racha: number } | null
}

type FilaJugador = {
    id: number
    apodo: string
    puntuacion: number
    correctas: number
    fallos: number
    sin_responder: number
    porcentaje_acierto: number
    tiempo_medio_ms: number | null
    mas_rapida_ms: number | null
    racha_maxima: number
    puntos_ganados: number
}

const props = defineProps<{
    estadisticas: {
        resumen: Resumen
        destacados: Destacados
        jugadores: FilaJugador[]
    }
    jugadorId?: number | null
    compacto?: boolean
}>()

const formatearMs = (ms: number | null | undefined) => {
    if (ms == null) {
        return '—'
    }
    return `${(ms / 1000).toFixed(2)} s`
}

const formatearDuracion = (segundos: number | null) => {
    if (segundos == null) {
        return '—'
    }
    const minutos = Math.floor(segundos / 60)
    const resto = segundos % 60
    return minutos > 0 ? `${minutos} m ${resto} s` : `${resto} s`
}
</script>

<template>
    <section class="panel-cosmo w-full space-y-5" aria-label="Estadísticas de la partida">
        <header class="text-center">
            <h2 class="text-xl text-slate-50">{{ compacto ? 'Estadísticas en vivo' : 'Estadísticas completas' }}</h2>
            <p class="mt-1 text-sm text-slate-400">
                {{ estadisticas.resumen.preguntas_con_respuestas }} /
                {{ estadisticas.resumen.preguntas_totales }} preguntas
            </p>
        </header>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="tarjeta-dato">
                <p class="etiqueta">Aciertos</p>
                <p class="valor text-ok">{{ estadisticas.resumen.porcentaje_acierto }}%</p>
                <p class="detalle">{{ estadisticas.resumen.aciertos }} / {{ estadisticas.resumen.respuestas }}</p>
            </div>
            <div class="tarjeta-dato">
                <p class="etiqueta">Tiempo medio</p>
                <p class="valor">{{ formatearMs(estadisticas.resumen.tiempo_medio_ms) }}</p>
                <p class="detalle">por respuesta</p>
            </div>
            <div class="tarjeta-dato">
                <p class="etiqueta">Puntos</p>
                <p class="valor">{{ estadisticas.resumen.puntos_repartidos }}</p>
                <p class="detalle">repartidos</p>
            </div>
            <div class="tarjeta-dato">
                <p class="etiqueta">Duración</p>
                <p class="valor">{{ formatearDuracion(estadisticas.resumen.duracion_segundos) }}</p>
                <p class="detalle">{{ estadisticas.resumen.jugadores }} jugadores</p>
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div v-if="estadisticas.destacados.mas_rapido" class="tarjeta-destaque">
                <p class="etiqueta">Más rápido</p>
                <p class="valor-sm">{{ estadisticas.destacados.mas_rapido.apodo }}</p>
                <p class="detalle">{{ formatearMs(estadisticas.destacados.mas_rapido.milisegundos) }}</p>
            </div>
            <div v-if="estadisticas.destacados.mas_preciso" class="tarjeta-destaque">
                <p class="etiqueta">Más preciso</p>
                <p class="valor-sm">{{ estadisticas.destacados.mas_preciso.apodo }}</p>
                <p class="detalle">{{ estadisticas.destacados.mas_preciso.porcentaje }}% acierto</p>
            </div>
            <div v-if="estadisticas.destacados.mayor_puntuacion" class="tarjeta-destaque">
                <p class="etiqueta">Más puntos</p>
                <p class="valor-sm">{{ estadisticas.destacados.mayor_puntuacion.apodo }}</p>
                <p class="detalle">{{ estadisticas.destacados.mayor_puntuacion.puntuacion }} pts</p>
            </div>
            <div v-if="estadisticas.destacados.mayor_racha" class="tarjeta-destaque">
                <p class="etiqueta">Mayor racha</p>
                <p class="valor-sm">{{ estadisticas.destacados.mayor_racha.apodo }}</p>
                <p class="detalle">{{ estadisticas.destacados.mayor_racha.racha }} seguidas</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-slate-400">
                        <th class="py-2 pr-2 font-normal">Jugador</th>
                        <th class="px-2 py-2 font-normal">Pts</th>
                        <th class="px-2 py-2 font-normal">✓</th>
                        <th class="px-2 py-2 font-normal">✗</th>
                        <th class="px-2 py-2 font-normal">%</th>
                        <th class="px-2 py-2 font-normal">Media</th>
                        <th class="px-2 py-2 font-normal">Rápida</th>
                        <th class="py-2 pl-2 font-normal">Racha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="fila in estadisticas.jugadores"
                        :key="fila.id"
                        class="border-b border-white/5"
                        :class="fila.id === jugadorId ? 'bg-white/10 text-slate-50' : 'text-slate-200'"
                    >
                        <td class="py-2.5 pr-2">
                            {{ fila.apodo }}
                            <span v-if="fila.id === jugadorId" class="text-xs text-slate-400"> · tú</span>
                        </td>
                        <td class="px-2 py-2.5">{{ fila.puntuacion }}</td>
                        <td class="px-2 py-2.5 text-ok">{{ fila.correctas }}</td>
                        <td class="px-2 py-2.5 text-ko">{{ fila.fallos }}</td>
                        <td class="px-2 py-2.5">{{ fila.porcentaje_acierto }}%</td>
                        <td class="px-2 py-2.5">{{ formatearMs(fila.tiempo_medio_ms) }}</td>
                        <td class="px-2 py-2.5">{{ formatearMs(fila.mas_rapida_ms) }}</td>
                        <td class="py-2.5 pl-2">{{ fila.racha_maxima }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<style scoped>
.tarjeta-dato,
.tarjeta-destaque {
    border-radius: 1rem;
    border: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(255 255 255 / 0.05);
    padding: 0.75rem 0.9rem;
}

.etiqueta {
    margin: 0;
    font-size: 0.7rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
}

.valor {
    margin: 0.2rem 0 0;
    font-size: 1.35rem;
    color: #f8fafc;
}

.valor-sm {
    margin: 0.2rem 0 0;
    font-size: 1.05rem;
    color: #f8fafc;
}

.detalle {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: #94a3b8;
}
</style>
