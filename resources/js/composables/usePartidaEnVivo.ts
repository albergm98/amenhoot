import { echo } from '@laravel/echo-vue'
import { onMounted, onUnmounted } from 'vue'

type Retrollamadas = {
    onJugadorUnido?: (payload: Record<string, unknown>) => void
    onPreguntaIniciada?: (payload: Record<string, unknown>) => void
    onPreguntaCerrada?: (payload: Record<string, unknown>) => void
    onPartidaFinalizada?: (payload: Record<string, unknown>) => void
    onRespuestaRecibida?: (payload: Record<string, unknown>) => void
    onPartidaPorEmpezar?: (payload: Record<string, unknown>) => void
}

export const usePartidaEnVivo = (
    pin: string,
    partidaId: number | null,
    retrollamadas: Retrollamadas,
) => {
    onMounted(() => {
        const canalPublico = echo().channel(`partida.${pin}`)

        if (retrollamadas.onJugadorUnido) {
            canalPublico.listen('.jugador.unido', retrollamadas.onJugadorUnido)
        }
        if (retrollamadas.onPreguntaIniciada) {
            canalPublico.listen('.pregunta.iniciada', retrollamadas.onPreguntaIniciada)
        }
        if (retrollamadas.onPreguntaCerrada) {
            canalPublico.listen('.pregunta.cerrada', retrollamadas.onPreguntaCerrada)
        }
        if (retrollamadas.onPartidaFinalizada) {
            canalPublico.listen('.partida.finalizada', retrollamadas.onPartidaFinalizada)
        }
        if (retrollamadas.onPartidaPorEmpezar) {
            canalPublico.listen('.partida.por-empezar', retrollamadas.onPartidaPorEmpezar)
        }

        if (partidaId && retrollamadas.onRespuestaRecibida) {
            echo()
                .private(`anfitrion.partida.${partidaId}`)
                .listen('.respuesta.recibida', retrollamadas.onRespuestaRecibida)
        }
    })

    onUnmounted(() => {
        echo().leave(`partida.${pin}`)
        if (partidaId) {
            echo().leave(`anfitrion.partida.${partidaId}`)
        }
    })
}
