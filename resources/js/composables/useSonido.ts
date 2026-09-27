import { onUnmounted, ref } from 'vue'

type TipoSonido = 'sala' | 'cuenta' | 'acierto' | 'fallo' | 'podio'

const frecuencias: Record<TipoSonido, number[]> = {
    sala: [220, 277, 330],
    cuenta: [880],
    acierto: [523, 659, 784],
    fallo: [200, 150],
    podio: [392, 523, 659, 784, 1046],
}

export const useSonido = () => {
    const silenciado = ref(localStorage.getItem('sonido_silenciado') === '1')
    let contexto: AudioContext | null = null
    let bucleSala: number | null = null

    const obtenerContexto = () => {
        if (!contexto) {
            contexto = new AudioContext()
        }
        return contexto
    }

    const tocarNotas = (notas: number[], duracion = 0.15, tipo: OscillatorType = 'sine') => {
        if (silenciado.value) {
            return
        }

        const ctx = obtenerContexto()
        void ctx.resume()

        notas.forEach((frecuencia, indice) => {
            const oscilador = ctx.createOscillator()
            const ganancia = ctx.createGain()
            oscilador.type = tipo
            oscilador.frequency.value = frecuencia
            ganancia.gain.value = 0.08
            oscilador.connect(ganancia)
            ganancia.connect(ctx.destination)
            const inicio = ctx.currentTime + indice * (duracion * 0.8)
            oscilador.start(inicio)
            ganancia.gain.exponentialRampToValueAtTime(0.001, inicio + duracion)
            oscilador.stop(inicio + duracion)
        })
    }

    const reproducir = (tipo: TipoSonido) => {
        tocarNotas(frecuencias[tipo], tipo === 'podio' ? 0.22 : 0.14, tipo === 'fallo' ? 'sawtooth' : 'sine')
    }

    const iniciarMusicaSala = () => {
        if (bucleSala || silenciado.value) {
            return
        }
        reproducir('sala')
        bucleSala = window.setInterval(() => reproducir('sala'), 4000)
    }

    const pararMusicaSala = () => {
        if (bucleSala) {
            clearInterval(bucleSala)
            bucleSala = null
        }
    }

    const alternarSilencio = () => {
        silenciado.value = !silenciado.value
        localStorage.setItem('sonido_silenciado', silenciado.value ? '1' : '0')
        if (silenciado.value) {
            pararMusicaSala()
        }
    }

    onUnmounted(() => {
        pararMusicaSala()
        void contexto?.close()
    })

    return {
        silenciado,
        reproducir,
        iniciarMusicaSala,
        pararMusicaSala,
        alternarSilencio,
    }
}
