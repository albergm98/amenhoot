import { onUnmounted, ref } from 'vue'

type MotivoVoz = 'linea' | 'bingo'

type SpeechRecognitionLike = {
    lang: string
    continuous: boolean
    interimResults: boolean
    start: () => void
    stop: () => void
    abort: () => void
    onresult: ((evento: SpeechRecognitionEventLike) => void) | null
    onerror: ((evento: { error: string }) => void) | null
    onend: (() => void) | null
}

type SpeechRecognitionEventLike = {
    resultIndex: number
    results: ArrayLike<{ isFinal: boolean; 0: { transcript: string } }>
}

const unidades = [
    'cero',
    'uno',
    'dos',
    'tres',
    'cuatro',
    'cinco',
    'seis',
    'siete',
    'ocho',
    'nueve',
    'diez',
    'once',
    'doce',
    'trece',
    'catorce',
    'quince',
    'dieciséis',
    'diecisiete',
    'dieciocho',
    'diecinueve',
]

const decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa']

export const numeroEnEspañol = (n: number): string => {
    if (n < 20) {
        return unidades[n] ?? String(n)
    }
    if (n === 20) {
        return 'veinte'
    }
    if (n < 30) {
        return `veinti${unidades[n - 20]}`
    }
    const d = Math.floor(n / 10)
    const u = n % 10
    if (u === 0) {
        return decenas[d]
    }
    return `${decenas[d]} y ${unidades[u]}`
}

const normalizar = (texto: string): string =>
    texto
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .replace(/[^a-z\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()

const obtenerReconocimiento = (): (new () => SpeechRecognitionLike) | null => {
    const w = window as Window & {
        SpeechRecognition?: new () => SpeechRecognitionLike
        webkitSpeechRecognition?: new () => SpeechRecognitionLike
    }
    return w.SpeechRecognition ?? w.webkitSpeechRecognition ?? null
}

export const useVozBingo = () => {
    const silenciado = ref(false)
    const microActivo = ref(false)
    const microSoportado = ref(Boolean(obtenerReconocimiento()))
    const microError = ref<string | null>(null)
    const hablando = ref(false)

    let reconocimiento: SpeechRecognitionLike | null = null
    let debeEscuchar = false
    let alDetectar: ((motivo: MotivoVoz) => void) | null = null
    let ignorarHasta = 0

    const cantarTexto = (texto: string): Promise<void> => {
        if (silenciado.value || typeof window === 'undefined' || !window.speechSynthesis) {
            return Promise.resolve()
        }
        window.speechSynthesis.cancel()
        const utterance = new SpeechSynthesisUtterance(texto)
        utterance.lang = 'es-ES'
        utterance.rate = 0.95
        hablando.value = true
        ignorarHasta = Date.now() + Math.max(2000, texto.length * 80)

        return new Promise((resolver) => {
            utterance.onend = () => {
                hablando.value = false
                ignorarHasta = Date.now() + 400
                resolver()
            }
            utterance.onerror = () => {
                hablando.value = false
                ignorarHasta = Date.now() + 400
                resolver()
            }
            window.speechSynthesis.speak(utterance)
        })
    }

    const cantarNumero = (bola: number): Promise<void> => cantarTexto(`Número ${numeroEnEspañol(bola)}`)

    const cantarImagen = (nombre: string): Promise<void> => cantarTexto(nombre.trim() || 'Desconocido')

    const procesarTexto = (texto: string) => {
        if (Date.now() < ignorarHasta || hablando.value) {
            return
        }
        const limpio = normalizar(texto)
        if (!limpio) {
            return
        }
        if (/\bbingo\b/.test(limpio)) {
            alDetectar?.('bingo')
            return
        }
        if (/\blinea\b/.test(limpio)) {
            alDetectar?.('linea')
        }
    }

    const arrancarMicro = () => {
        const Fabrica = obtenerReconocimiento()
        if (!Fabrica) {
            microSoportado.value = false
            microError.value = 'Tu navegador no reconoce voz. Usa Chrome o Edge.'
            return
        }
        microError.value = null
        debeEscuchar = true
        if (reconocimiento) {
            try {
                reconocimiento.abort()
            } catch {
                /* vacío */
            }
        }
        reconocimiento = new Fabrica()
        reconocimiento.lang = 'es-ES'
        reconocimiento.continuous = true
        reconocimiento.interimResults = true
        reconocimiento.onresult = (evento) => {
            for (let i = evento.resultIndex; i < evento.results.length; i += 1) {
                const resultado = evento.results[i]
                procesarTexto(resultado[0].transcript)
            }
        }
        reconocimiento.onerror = (evento) => {
            if (evento.error === 'not-allowed') {
                microError.value = 'Permite el micrófono para detectar línea o bingo.'
                microActivo.value = false
                debeEscuchar = false
            }
        }
        reconocimiento.onend = () => {
            microActivo.value = false
            if (debeEscuchar) {
                try {
                    reconocimiento?.start()
                    microActivo.value = true
                } catch {
                    /* vacío */
                }
            }
        }
        try {
            reconocimiento.start()
            microActivo.value = true
        } catch {
            microError.value = 'No se pudo arrancar el micrófono.'
        }
    }

    const pararMicro = () => {
        debeEscuchar = false
        microActivo.value = false
        try {
            reconocimiento?.stop()
        } catch {
            /* vacío */
        }
        reconocimiento = null
    }

    const onPalabraClave = (fn: (motivo: MotivoVoz) => void) => {
        alDetectar = fn
    }

    const alternarSilencio = () => {
        silenciado.value = !silenciado.value
        if (silenciado.value && window.speechSynthesis) {
            window.speechSynthesis.cancel()
            hablando.value = false
        }
    }

    onUnmounted(() => {
        pararMicro()
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel()
        }
    })

    return {
        silenciado,
        microActivo,
        microSoportado,
        microError,
        hablando,
        cantarNumero,
        cantarImagen,
        cantarTexto,
        arrancarMicro,
        pararMicro,
        onPalabraClave,
        alternarSilencio,
        numeroEnEspañol,
    }
}
