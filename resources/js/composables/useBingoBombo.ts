import { computed, onUnmounted, ref, watch } from 'vue'

export type FichaBingo = {
    id: number
    url: string
    columna: string
    etiqueta: string
}

const barajar = <T>(items: T[]): T[] => {
    const copia = [...items]
    for (let i = copia.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1))
        ;[copia[i], copia[j]] = [copia[j], copia[i]]
    }
    return copia
}

export const useBingoBombo = (catalogo: () => FichaBingo[]) => {
    const pendientes = ref<FichaBingo[]>([])
    const sacadas = ref<FichaBingo[]>([])
    const bolaActual = ref<FichaBingo | null>(null)
    const enMarcha = ref(false)
    const pausado = ref(false)
    const segundosEntreBolas = ref(5)
    const motivoPausa = ref<'manual' | 'linea' | 'bingo' | null>(null)

    let temporizador: number | null = null
    let alSacar: ((ficha: FichaBingo) => void | Promise<void>) | null = null

    const total = computed(() => catalogo().length)
    const quedan = computed(() => pendientes.value.length)
    const terminado = computed(
        () => total.value > 0 && sacadas.value.length >= total.value && pendientes.value.length === 0,
    )
    const idsSacados = computed(() => new Set(sacadas.value.map((ficha) => ficha.id)))

    const limpiarTemporizador = () => {
        if (temporizador !== null) {
            window.clearTimeout(temporizador)
            temporizador = null
        }
    }

    const reiniciar = () => {
        limpiarTemporizador()
        pendientes.value = barajar(catalogo())
        sacadas.value = []
        bolaActual.value = null
        enMarcha.value = false
        pausado.value = false
        motivoPausa.value = null
    }

    const programarSiguiente = () => {
        limpiarTemporizador()
        if (!enMarcha.value || pausado.value || pendientes.value.length === 0) {
            if (pendientes.value.length === 0) {
                enMarcha.value = false
            }
            return
        }
        temporizador = window.setTimeout(() => {
            void sacarSiguiente()
        }, segundosEntreBolas.value * 1000)
    }

    const sacarSiguiente = async (): Promise<FichaBingo | null> => {
        if (pausado.value || pendientes.value.length === 0) {
            enMarcha.value = pendientes.value.length > 0 && enMarcha.value
            return null
        }
        limpiarTemporizador()
        const ficha = pendientes.value.shift() ?? null
        if (ficha === null) {
            enMarcha.value = false
            return null
        }
        bolaActual.value = ficha
        sacadas.value = [...sacadas.value, ficha]
        if (alSacar) {
            await alSacar(ficha)
        }
        if (enMarcha.value && !pausado.value) {
            programarSiguiente()
        }
        return ficha
    }

    const empezar = async () => {
        if (pendientes.value.length === 0 && sacadas.value.length === 0) {
            reiniciar()
        }
        if (pendientes.value.length === 0) {
            return
        }
        enMarcha.value = true
        pausado.value = false
        motivoPausa.value = null
        await sacarSiguiente()
    }

    const pausar = (motivo: 'manual' | 'linea' | 'bingo' = 'manual') => {
        pausado.value = true
        motivoPausa.value = motivo
        limpiarTemporizador()
    }

    const reanudar = () => {
        if (!pausado.value) {
            return
        }
        pausado.value = false
        motivoPausa.value = null
        enMarcha.value = true
        programarSiguiente()
    }

    const alCantarBola = (fn: (ficha: FichaBingo) => void | Promise<void>) => {
        alSacar = fn
    }

    reiniciar()

    watch(
        () => catalogo().length,
        () => {
            if (sacadas.value.length === 0 && !enMarcha.value) {
                reiniciar()
            }
        },
    )

    onUnmounted(() => {
        limpiarTemporizador()
    })

    return {
        pendientes,
        sacadas,
        bolaActual,
        enMarcha,
        pausado,
        segundosEntreBolas,
        motivoPausa,
        quedan,
        terminado,
        total,
        idsSacados,
        reiniciar,
        empezar,
        pausar,
        reanudar,
        sacarSiguiente,
        alCantarBola,
    }
}
