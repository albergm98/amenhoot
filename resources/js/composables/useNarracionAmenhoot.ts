import type { Ref } from 'vue'
import { onUnmounted, ref } from 'vue'

type OpcionesNarracion = {
    silenciado: Ref<boolean>
    alEmpezar?: () => void
    alTerminar?: () => void
}

const urlBase = '/assets/audio/amenhoot'

export const rutaAudioNormas = (): string => `${urlBase}/normas.wav`

export const rutaAudioPregunta = (cuestionarioId: number, orden: number): string =>
    `${urlBase}/c${cuestionarioId}/pregunta-${orden}.wav`

export const useNarracionAmenhoot = (opciones: OpcionesNarracion) => {
    const hablando = ref(false)
    let audioActual: HTMLAudioElement | null = null

    const limpiarAudio = () => {
        if (!audioActual) {
            return
        }
        audioActual.onended = null
        audioActual.onerror = null
        audioActual.pause()
        audioActual.src = ''
        audioActual = null
    }

    const cancelar = (avisarFin = true) => {
        limpiarAudio()
        if (typeof window !== 'undefined' && window.speechSynthesis) {
            window.speechSynthesis.cancel()
        }
        if (hablando.value) {
            hablando.value = false
            if (avisarFin) {
                opciones.alTerminar?.()
            }
        }
    }

    const hablarFallback = (texto: string): Promise<void> => {
        if (typeof window === 'undefined' || !window.speechSynthesis) {
            return Promise.resolve()
        }
        window.speechSynthesis.cancel()
        const utterance = new SpeechSynthesisUtterance(texto)
        utterance.lang = 'es-ES'
        utterance.rate = 0.95

        return new Promise((resolver) => {
            utterance.onend = () => resolver()
            utterance.onerror = () => resolver()
            window.speechSynthesis.speak(utterance)
        })
    }

    const reproducirArchivo = (ruta: string): Promise<boolean> =>
        new Promise((resolver) => {
            const audio = new Audio(ruta)
            audioActual = audio
            audio.onended = () => {
                limpiarAudio()
                resolver(true)
            }
            audio.onerror = () => {
                limpiarAudio()
                resolver(false)
            }
            void audio.play().catch(() => {
                limpiarAudio()
                resolver(false)
            })
        })

    const narrar = async (ruta: string, textoFallback: string): Promise<void> => {
        if (opciones.silenciado.value) {
            return
        }
        cancelar(false)
        hablando.value = true
        opciones.alEmpezar?.()

        const ok = await reproducirArchivo(ruta)
        if (!ok && !opciones.silenciado.value) {
            await hablarFallback(textoFallback)
        }

        if (hablando.value) {
            hablando.value = false
            opciones.alTerminar?.()
        }
    }

    const narrarNormas = (textoFallback: string): Promise<void> =>
        narrar(rutaAudioNormas(), textoFallback)

    const narrarEnunciado = (
        cuestionarioId: number,
        orden: number,
        enunciado: string,
    ): Promise<void> => narrar(rutaAudioPregunta(cuestionarioId, orden), enunciado)

    onUnmounted(() => {
        cancelar()
    })

    return {
        hablando,
        narrarNormas,
        narrarEnunciado,
        cancelar,
        rutaAudioNormas,
        rutaAudioPregunta,
    }
}
